#!/usr/bin/env bash

# ----------------------------------------------------------------------------------------------------------------------
# fgtclb/typo3-oauth2-server test runner based on docker/podman.
# Adopted from TYPO3 Core Development and fgtclb/academic-extensions.
# ----------------------------------------------------------------------------------------------------------------------
if [ "${CI}" != "true" ]; then
    trap 'echo "runTests.sh SIGINT signal emitted";cleanUp;exit 2' SIGINT
fi

printSummary() {
    cleanUp

    # Print summary
    echo "" >&2
    echo "###########################################################################" >&2
    echo "Result of ${TEST_SUITE}" >&2
    echo "Container runtime: ${CONTAINER_BIN}" >&2
    echo "Container suffix: ${SUFFIX}"
    if [[ ${IS_CORE_CI} -eq 1 ]]; then
        echo "Environment: CI" >&2
    else
        echo "Environment: local" >&2
    fi
    echo "PHP: ${PHP_VERSION}" >&2
    echo "TYPO3: ${CORE_VERSION}" >&2
    if [[ -n "${RENDERER_INFO}" ]]; then
        echo "Renderer: ${RENDERER_INFO}" >&2
    fi
    if [[ ${TEST_SUITE} =~ ^(functional|functionalDeprecated|acceptance|acceptanceInstall)$ ]]; then
        case "${DBMS}" in
            mariadb|mysql|postgres)
                echo "DBMS: ${DBMS}  version ${DBMS_VERSION}  driver ${DATABASE_DRIVER}" >&2
                ;;
            sqlite)
                echo "DBMS: ${DBMS}" >&2
                ;;
        esac
    fi
    if [[ ${SUITE_EXIT_CODE} -eq 0 ]]; then
        echo "SUCCESS" >&2
    else
        echo "FAILURE" >&2
    fi
    echo "###########################################################################" >&2
    echo "" >&2

    # Exit with code of test suite - This script return non-zero if the executed test failed.
    exit $SUITE_EXIT_CODE
}

# Make sure an image is available locally, retrying a failed pull.
#
# `run` pulls implicitly on a cache miss, and a single failed pull ends the whole
# job with exit 125 before a test has run. Anonymous Docker Hub pulls are rate
# limited per source IP and hosted runners share address space, so the docker.io
# images are guarded here, and the renderer when it is not cached, see "pullRenderer".
#
# The local check comes first so a warm cache still works offline and a pinned
# image is not re-fetched on every local run.
ensureImage() {
    local IMAGE=${1}
    local MAX_ATTEMPTS=3
    local ATTEMPT=1
    if ${CONTAINER_BIN} image inspect "${IMAGE}" >/dev/null 2>&1; then
        return 0
    fi
    while [ ${ATTEMPT} -le ${MAX_ATTEMPTS} ]; do
        if ${CONTAINER_BIN} pull "${IMAGE}"; then
            return 0
        fi
        if [ ${ATTEMPT} -lt ${MAX_ATTEMPTS} ]; then
            echo "Pulling ${IMAGE} failed (attempt ${ATTEMPT} of ${MAX_ATTEMPTS}), retrying in $((ATTEMPT * 5))s." >&2
            sleep $((ATTEMPT * 5))
        fi
        ATTEMPT=$((ATTEMPT + 1))
    done
    echo "Could not pull ${IMAGE} after ${MAX_ATTEMPTS} attempts. Aborting." >&2
    return 1
}

# Pull the documentation renderer before a run renders with it.
#
# The renderer is used by its "latest" tag on purpose, so the manual is checked
# with the release docs.typo3.org renders it with. Neither docker nor podman
# refreshes a tag they already have, so without this pull a local run keeps
# rendering with whatever release was pulled last.
#
# Without a cached image the pull is left to "ensureImage" and its retries, and a
# run without any renderer fails. With a cached image a failed pull does not fail
# the run: it is reported on stderr and again in the summary, because that run
# may not render what docs.typo3.org renders.
pullRenderer() {
    local IMAGE=${1}
    local PULL_STATE=""
    if ! ${CONTAINER_BIN} image inspect "${IMAGE}" >/dev/null 2>&1; then
        ensureImage "${IMAGE}" || return 1
    elif ! ${CONTAINER_BIN} pull "${IMAGE}"; then
        echo "" >&2
        echo "###########################################################################" >&2
        echo "WARNING: Could not pull ${IMAGE}." >&2
        echo "Rendering with the cached image, which may be older than the release" >&2
        echo "docs.typo3.org renders with." >&2
        echo "###########################################################################" >&2
        echo "" >&2
        PULL_STATE=" (cached, pull failed)"
    fi
    RENDERER_INFO="render-guides $(${CONTAINER_BIN} image inspect "${IMAGE}" --format '{{index .Config.Labels "org.opencontainers.image.version"}}')${PULL_STATE}"
    echo "Renderer: ${IMAGE}, ${RENDERER_INFO}" >&2
}

waitFor() {
    local HOST=${1}
    local PORT=${2}
    # 60 rather than 10 seconds: mysql:8.0 needs 12-13s under docker to initialise a fresh
    # data directory, about twice as long as under podman, so an 11 second budget aborted
    # the functional mysql suites at random.
    local TESTCOMMAND="
        COUNT=0;
        while ! nc -z ${HOST} ${PORT}; do
            if [ \"\${COUNT}\" -gt 60 ]; then
              echo \"Can not connect to ${HOST} port ${PORT}. Aborting.\";
              exit 1;
            fi;
            sleep 1;
            COUNT=\$((COUNT + 1));
        done;
    "
    ensureImage "${IMAGE_ALPINE}" || { cleanUp; exit 1; }
    ${CONTAINER_BIN} run ${CONTAINER_COMMON_PARAMS} --name wait-for-${SUFFIX} ${XDEBUG_MODE} -e XDEBUG_CONFIG="${XDEBUG_CONFIG}" ${IMAGE_ALPINE} /bin/sh -c "${TESTCOMMAND}"
    if [[ $? -gt 0 ]]; then
        # Not "kill -SIGINT -$$": the SIGINT trap is only installed when CI is not "true",
        # so in CI the signal was a no-op, the run continued and the test suite connected
        # to a database that was not listening.
        cleanUp
        exit 1
    fi
}

cleanUp() {
    ATTACHED_CONTAINERS=$(${CONTAINER_BIN} ps --filter network=${NETWORK} --format='{{.Names}}')
    for ATTACHED_CONTAINER in ${ATTACHED_CONTAINERS}; do
        ${CONTAINER_BIN} rm -f ${ATTACHED_CONTAINER} >/dev/null
    done
    ${CONTAINER_BIN} network rm -f ${NETWORK} >/dev/null
}

handleDbmsOptions() {
    # -a, -d, -i depend on each other. Validate input combinations and set defaults.
    case ${DBMS} in
        mariadb)
            [ -z "${DATABASE_DRIVER}" ] && DATABASE_DRIVER="mysqli"
            if [ "${DATABASE_DRIVER}" != "mysqli" ] && [ "${DATABASE_DRIVER}" != "pdo_mysql" ]; then
                echo "Invalid combination -d ${DBMS} -a ${DATABASE_DRIVER}" >&2
                echo >&2
                echo "Use \".Build/Scripts/runTests.sh -h\" to display help and valid options" >&2
                exit 1
            fi
            [ -z "${DBMS_VERSION}" ] && DBMS_VERSION="10.4"
            if ! [[ ${DBMS_VERSION} =~ ^(10.4|10.5|10.6|10.7|10.8|10.9|10.10|10.11|11.0|11.1|11.2|11.3|11.4)$ ]]; then
                echo "Invalid combination -d ${DBMS} -i ${DBMS_VERSION}" >&2
                echo >&2
                echo "Use \".Build/Scripts/runTests.sh -h\" to display help and valid options" >&2
                exit 1
            fi
            ;;
        mysql)
            [ -z "${DATABASE_DRIVER}" ] && DATABASE_DRIVER="mysqli"
            if [ "${DATABASE_DRIVER}" != "mysqli" ] && [ "${DATABASE_DRIVER}" != "pdo_mysql" ]; then
                echo "Invalid combination -d ${DBMS} -a ${DATABASE_DRIVER}" >&2
                echo >&2
                echo "Use \".Build/Scripts/runTests.sh -h\" to display help and valid options" >&2
                exit 1
            fi
            [ -z "${DBMS_VERSION}" ] && DBMS_VERSION="8.0"
            if ! [[ ${DBMS_VERSION} =~ ^(8.0|8.1|8.2|8.3|8.4)$ ]]; then
                echo "Invalid combination -d ${DBMS} -i ${DBMS_VERSION}" >&2
                echo >&2
                echo "Use \".Build/Scripts/runTests.sh -h\" to display help and valid options" >&2
                exit 1
            fi
            ;;
        postgres)
            if [ -n "${DATABASE_DRIVER}" ]; then
                echo "Invalid combination -d ${DBMS} -a ${DATABASE_DRIVER}" >&2
                echo >&2
                echo "Use \".Build/Scripts/runTests.sh -h\" to display help and valid options" >&2
                exit 1
            fi
            [ -z "${DBMS_VERSION}" ] && DBMS_VERSION="10"
            if ! [[ ${DBMS_VERSION} =~ ^(10|11|12|13|14|15|16)$ ]]; then
                echo "Invalid combination -d ${DBMS} -i ${DBMS_VERSION}" >&2
                echo >&2
                echo "Use \".Build/Scripts/runTests.sh -h\" to display help and valid options" >&2
                exit 1
            fi
            ;;
        sqlite)
            if [ -n "${DATABASE_DRIVER}" ]; then
                echo "Invalid combination -d ${DBMS} -a ${DATABASE_DRIVER}" >&2
                echo >&2
                echo "Use \".Build/Scripts/runTests.sh -h\" to display help and valid options" >&2
                exit 1
            fi
            if [ -n "${DBMS_VERSION}" ]; then
                echo "Invalid combination -d ${DBMS} -i ${DATABASE_DRIVER}" >&2
                echo >&2
                echo "Use \".Build/Scripts/runTests.sh -h\" to display help and valid options" >&2
                exit 1
            fi
            ;;
        *)
            echo "Invalid option -d ${DBMS}" >&2
            echo >&2
            echo "Use \".Build/Scripts/runTests.sh -h\" to display help and valid options" >&2
            exit 1
            ;;
    esac
}

cleanCacheFiles() {
    echo -n "Clean caches ... "
    rm -rf \
        .cache \
        .Build/.cache \
        .php-cs-fixer.cache
    echo "done"
}

cleanTestFiles() {
    # test related
    echo -n "Clean test related files ... "
    rm -rf \
        .Build/Web/typo3temp/var/tests/
    echo "done"
}

cleanRenderedDocumentationFiles() {
    echo -n "Clean rendered documentation files ... "
    rm -rf \
        Documentation-GENERATED-temp
    echo "done"
}

loadHelp() {
    # Load help text into $HELP
    read -r -d '' HELP <<EOF
TYPO3 core test runner. Execute acceptance, unit, functional and other test suites in
a container based test environment. Handles execution of single test files, sending
xdebug information to a local IDE and more.

Usage: $0 [options] [file]

Options:
    -s <...>
        Specifies which test suite to run
            - cgl: cgl test and fix all php files
            - checkBom: check UTF-8 files do not contain BOM
            - checkExceptionCodes: Check for duplicate exception codes
            - checkRst: test .rst files for integrity
            - checkTestMethodsPrefix: check tests methods do not start with "test"
            - clean: clean up build, cache and testing related files and folders
            - cleanCache: clean up cache related files and folders
            - cleanRenderedDocumentation: clean up rendered documentation files and folders (Documentation-GENERATED-temp)
            - clean: clean up build and testing related files
            - composer: "composer" with all remaining arguments dispatched.
            - composerUpdate: "composer install" against the TYPO3 core version selected with "-t", handy if host has no PHP
            - functional: functional tests
            - lintPhp: PHP linting
            - lintTypoScript: TypoScript linting
            - renderDocumentation: This uses the official rendering container to render the extension documentation.
            - phpstan: phpstan analyze
            - phpstanGenerateBaseline: regenerate phpstan baseline, handy after phpstan updates
            - unit: PHP unit tests
            - unitRandom: PHP unit tests in random order, "-o <number>" to use a specific seed
            - update: update the typo3/core-testing-* images, same as "-u"
            - help: show this help

    -b <docker|podman>
        Container environment:
            - docker
            - podman

        If not specified, podman will be used if available. Otherwise, docker is used.

    -a <mysqli|pdo_mysql>
        Only with -s functional|functionalDeprecated
        Specifies to use another driver, following combinations are available:
            - mysql
                - mysqli (default)
                - pdo_mysql
            - mariadb
                - mysqli (default)
                - pdo_mysql

    -d <sqlite|mariadb|mysql|postgres>
        Only with -s functional
        Specifies on which DBMS tests are performed
            - sqlite: (default): use sqlite
            - mariadb: use mariadb
            - mysql: use MySQL
            - postgres: use postgres

    -i version
        Specify a specific database version
        With "-d mariadb":
            - 10.4   short-term, maintained until 2024-06-18 (default)
            - 10.5   short-term, maintained until 2025-06-24
            - 10.6   long-term, maintained until 2026-06
            - 10.7   short-term, no longer maintained
            - 10.8   short-term, maintained until 2023-05
            - 10.9   short-term, maintained until 2023-08
            - 10.10  short-term, maintained until 2023-11
            - 10.11  long-term, maintained until 2028-02
            - 11.0   development series
            - 11.1   short-term development series
        With "-d mysql":
            - 8.0   maintained until 2026-04 (default)
            - 8.1   unmaintained since 2023-10
            - 8.2   unmaintained since 2024-01
            - 8.3   maintained until 2024-04
            - 8.4   maintained until 2032-04 LTS
        With "-d postgres":
            - 10    unmaintained since 2022-11-10 (default)
            - 11    maintained until 2023-11-09
            - 12    maintained until 2024-11-14
            - 13    maintained until 2025-11-13
            - 14    maintained until 2026-11-12
            - 15    maintained until 2027-11-11
            - 16    maintained until 2028-11-09

    -t <12>
        Only with -s composerUpdate|phpstan
        Specifies the TYPO3 CORE Version to be used
            - 12: use TYPO3 v12 (default)

    -p <8.1|8.2|8.3|8.4>
        Specifies the PHP minor version to be used
            - 8.1: use PHP 8.1 (default)
            - 8.2: use PHP 8.2
            - 8.3: use PHP 8.3
            - 8.4: use PHP 8.4

    -x
        Only with -s functional|unit|unitRandom
        Send information to host instance for test or system under test break points. This is especially
        useful if a local PhpStorm instance is listening on default xdebug port 9003. A different port
        can be selected with -y

    -y <port>
        Send xdebug information to a different port than default 9003 if an IDE like PhpStorm
        is not listening on default port.

    -o <number>
        Only with -s unitRandom
        Set specific random seed to replay a random run in this order again. The phpunit randomizer
        outputs the used seed at the end (in gitlab core testing logs, too). Use that number to
        replay the unit tests in that order.

    -n
        Only with -s cgl
        Activate dry-run in CGL check that does not actively change files and only prints broken ones.

    -u
        Update existing typo3/core-testing-*:latest container images and remove dangling local volumes.
        New images are published once in a while and only the latest ones are supported by core testing.
        Use this if weird test errors occur. Also removes obsolete image versions of typo3/core-testing-*.

    -h
        Show this help.

Examples:
    # Run all core unit tests using PHP 8.1
    ./Build/Scripts/runTests.sh
    ./Build/Scripts/runTests.sh -s unit

    # Run all core units tests and enable xdebug (have a PhpStorm listening on port 9003!)
    ./Build/Scripts/runTests.sh -x

    # Run unit tests in phpunit verbose mode with xdebug on PHP 8.1 and filter for test canRetrieveValueWithGP
    ./Build/Scripts/runTests.sh -x -p 8.1 -e "-v --filter canRetrieveValueWithGP"

    # Run functional tests in phpunit with a filtered test method name in a specified file
    # example will currently execute two tests, both of which start with the search term
    ./Build/Scripts/runTests.sh -s functional -e "--filter deleteContent" typo3/sysext/core/Tests/Functional/DataHandling/Regular/Modify/ActionTest.php

    # Run functional tests on postgres with xdebug, php 8.1 and execute a restricted set of tests
    ./Build/Scripts/runTests.sh -x -p 8.1 -s functional -d postgres typo3/sysext/core/Tests/Functional/Authentication

    # Run functional tests on postgres 11
    ./Build/Scripts/runTests.sh -s functional -d postgres -k 11

    # Run restricted set of application acceptance tests
    ./Build/Scripts/runTests.sh -s acceptance typo3/sysext/core/Tests/Acceptance/Application/Login/BackendLoginCest.php:loginButtonMouseOver

    # Run installer tests of a new instance on sqlite
    ./Build/Scripts/runTests.sh -s acceptanceInstall -d sqlite
EOF
}

# Test if docker exists, else exit out with error
if ! type "docker" >/dev/null 2>&1 && ! type "podman" >/dev/null 2>&1; then
    echo "This script relies on docker or podman. Please install" >&2
    exit 1
fi

# Option defaults
TEST_SUITE="unit"
CORE_VERSION="12"
DBMS="sqlite"
PHP_VERSION="8.1"
PHP_XDEBUG_ON=0
PHP_XDEBUG_PORT=9003
PHPUNIT_RANDOM=""
CGLCHECK_DRY_RUN=0
DATABASE_DRIVER=""
DBMS_VERSION=""
CONTAINER_BIN=""
CONTAINER_HOST="host.docker.internal"

# Option parsing updates above default vars
# Reset in case getopts has been used previously in the shell
OPTIND=1
# Array for invalid options
INVALID_OPTIONS=()
# Simple option parsing based on getopts (! not getopt)
while getopts "a:b:s:d:i:p:t:xy:o:nhu" OPT; do
    case ${OPT} in
        s)
            TEST_SUITE=${OPTARG}
            ;;
        b)
            if ! [[ ${OPTARG} =~ ^(docker|podman)$ ]]; then
                INVALID_OPTIONS+=("${OPTARG}")
            fi
            CONTAINER_BIN=${OPTARG}
            ;;
        a)
            DATABASE_DRIVER=${OPTARG}
            ;;
        d)
            DBMS=${OPTARG}
            ;;
        i)
            DBMS_VERSION=${OPTARG}
            ;;
        p)
            PHP_VERSION=${OPTARG}
            if ! [[ ${PHP_VERSION} =~ ^(8.1|8.2|8.3|8.4)$ ]]; then
                INVALID_OPTIONS+=("p ${OPTARG}")
            fi
            ;;
        t)
            CORE_VERSION=${OPTARG}
            if ! [[ ${CORE_VERSION} =~ ^(12)$ ]]; then
                INVALID_OPTIONS+=("t ${OPTARG}")
            fi
            ;;
        x)
            PHP_XDEBUG_ON=1
            ;;
        y)
            PHP_XDEBUG_PORT=${OPTARG}
            ;;
        o)
            PHPUNIT_RANDOM="--random-order-seed=${OPTARG}"
            ;;
        n)
            CGLCHECK_DRY_RUN=1
            ;;
        h)
            loadHelp
            echo "${HELP}"
            exit 0
            ;;
        u)
            TEST_SUITE=update
            ;;
        \?)
            INVALID_OPTIONS+=("${OPTARG}")
            ;;
        :)
            INVALID_OPTIONS+=("${OPTARG}")
            ;;
    esac
done

# Exit on invalid options
if [ ${#INVALID_OPTIONS[@]} -ne 0 ]; then
    echo "Invalid option(s):" >&2
    for I in "${INVALID_OPTIONS[@]}"; do
        echo "-"${I} >&2
    done
    echo >&2
    echo "call \".Build/Scripts/runTests.sh -h\" to display help and valid options"
    exit 1
fi

handleDbmsOptions

COMPOSER_ROOT_VERSION="3.0.0-dev"
CONTAINER_INTERACTIVE="-it --init"
HOST_UID=$(id -u)
HOST_GID=$(id -g)
# Additional container runtime parameters, empty unless the calling environment exports them.
CI_PARAMS="${CI_PARAMS:-}"

# Composer authentication, forwarded into the container it runs in.
#
# Composer fetches every dist archive from "api.github.com" and "codeload.github.com".
# Unauthenticated that is 60 requests per hour and per IP, which lets "composerUpdate"
# die with "HTTP/2 429" on a cold cache. A token lifts the limit to 1000 requests per hour.
#
# The value is deliberately *not* written into the "-e" argument. A bare "-e COMPOSER_AUTH"
# tells the runtime to take the value from this process's environment, so the token never
# lands on a command line and cannot show up in the process list of the host.
COMPOSER_AUTH_ENV=""
if [ -n "${COMPOSER_AUTH:-}" ]; then
    COMPOSER_AUTH_ENV="-e COMPOSER_AUTH"
fi
USERSET=""
if [ $(uname) != "Darwin" ]; then
    USERSET="--user $HOST_UID"
fi

# Go to the directory this script is located, so everything else is relative
# to this dir, no matter from where this script is called, then go up two dirs.
THIS_SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" >/dev/null && pwd)"
cd "$THIS_SCRIPT_DIR" || exit 1
cd ../../ || exit 1
ROOT_DIR="${PWD}"

# Create .cache dir: composer need this.
mkdir -p .cache/composer
mkdir -p .Build/Web/typo3temp/var/tests


IS_CORE_CI=0
# ENV var "CI" is set by gitlab-ci. We use it here to distinct 'local' and 'CI' environment.
if [ "${CI}" == "true" ]; then
    IS_CORE_CI=1
    IMAGE_PREFIX=""
    CONTAINER_INTERACTIVE=""
elif [ ! -t 0 ] || [ ! -t 1 ]; then
    # If stdin or stdout is not a TTY (e.g. a script runner, pipe, or non-interactive shell),
    # drop the interactive "-it" flags automatically to avoid podman warning "The input device
    # is not a TTY." and docker failure, and to keep redirected output free of TTY control characters.
    # Keep "--init" so the PID 1 init process still forwards signals (e.g. ctrl-c) to the test process.
    #
    # It also stops a scripted run from hanging forever: with a pseudo TTY every tool inside the
    # container believes it may ask a question, and composer does - it asks whether a plugin missing
    # from "allow-plugins" is trusted, and then waits for an answer nobody is there to give.
    CONTAINER_INTERACTIVE="--init"
fi

# determine default container binary to use: 1. podman 2. docker
if [[ -z "${CONTAINER_BIN}" ]]; then
    if type "podman" >/dev/null 2>&1; then
        CONTAINER_BIN="podman"
    elif type "docker" >/dev/null 2>&1; then
        CONTAINER_BIN="docker"
    fi
fi

IMAGE_PHP="ghcr.io/typo3/core-testing-$(echo "php${PHP_VERSION}" | sed -e 's/\.//'):latest"
IMAGE_ALPINE="docker.io/alpine:3.8"
# The renderer of docs.typo3.org, by its "latest" tag on purpose and pulled before
# every documentation run, see "pullRenderer".
IMAGE_DOCS="ghcr.io/typo3-documentation/render-guides:latest"
IMAGE_SELENIUM="docker.io/selenium/standalone-chrome:4.0.0-20211102"
IMAGE_MARIADB="docker.io/mariadb:${DBMS_VERSION}"
IMAGE_MYSQL="docker.io/mysql:${DBMS_VERSION}"
IMAGE_POSTGRES="docker.io/postgres:${DBMS_VERSION}-alpine"
# Server options for the MySQL and MariaDB test containers. Their data directory is a
# tmpfs and is thrown away with the container, so durability buys nothing: no binary
# log (on by default in MySQL 8, off in MariaDB anyway), the redo log written but not
# flushed on every commit, and no doublewrite buffer.
MYSQL_SERVER_OPTIONS="--skip-log-bin --innodb-flush-log-at-trx-commit=2 --innodb-doublewrite=0"


# Detect arm64 and use a seleniarm image.
# In a perfect world selenium would have a arm64 integrated, but that is not on the horizon.
# So for the time being we have to use seleniarm image.
ARCH=$(uname -m)
if [ ${ARCH} = "arm64" ]; then
    IMAGE_SELENIUM="docker.io/seleniarm/standalone-chromium:4.1.2-20220227"
fi
# echo "Architecture" ${ARCH} "requires" ${IMAGE_SELENIUM} "to run acceptance tests."

# Set $1 to first mass argument, this is the optional test file or test directory to execute
shift $((OPTIND - 1))

SUFFIX=$(echo $RANDOM)
NETWORK="typo3-oauth2-server-${SUFFIX}"
${CONTAINER_BIN} network create ${NETWORK} >/dev/null

if [ "${CONTAINER_BIN}" == "docker" ]; then
    # docker needs the add-host for xdebug remote debugging. podman has host.container.internal built in
    CONTAINER_COMMON_PARAMS="${CONTAINER_INTERACTIVE} --rm --network ${NETWORK} --add-host ${CONTAINER_HOST}:host-gateway ${USERSET} -v ${ROOT_DIR}:${ROOT_DIR} -w ${ROOT_DIR}"
    CONTAINER_SIMPLE_PARAMS="${CONTAINER_INTERACTIVE} --rm --network ${NETWORK} --add-host ${CONTAINER_HOST}:host-gateway ${USERSET} -v ${ROOT_DIR}:${ROOT_DIR} -w ${ROOT_DIR}"
    DOCUMENTATION_COMMON_PARAMS="${CONTAINER_INTERACTIVE} --rm ${USERSET} -v ${ROOT_DIR}:/project"
    # docker creates the tmpfs owned by "root:root", while "${USERSET}" above passes a uid
    # but no group, so the container runs as "uid=${HOST_UID} gid=0" and does not own the
    # mount it has to write the test databases into. Whether that still works then depends
    # on the mode docker happens to give the tmpfs: 1777 by default on docker 29, but 0755
    # where it follows the umask, and then no test database can be created and every test
    # fails with "unable to open database file".
    #
    # "uid"/"gid" remove that dependency at the source - the mount is owned by the user the
    # container runs as. "mode=1777" is kept next to them as the proven fallback.
    TMPFS_MOUNT_OPTIONS="rw,noexec,nosuid,uid=${HOST_UID},gid=${HOST_GID},mode=1777"
else
    # podman
    CONTAINER_HOST="host.containers.internal"
    # Rootless podman maps the container root to the host user, so the tmpfs is writable
    # without an explicit owner. "mode=1777" is kept for the rootful case.
    TMPFS_MOUNT_OPTIONS="rw,noexec,nosuid,mode=1777"
    if [ $( uname ) = "Linux" ]; then
        # Relabel the bind mounts for SELinux.
        CONTAINER_COMMON_PARAMS="${CONTAINER_INTERACTIVE} ${CI_PARAMS} --rm --network ${NETWORK} -v ${ROOT_DIR}:${ROOT_DIR}:Z -w ${ROOT_DIR}"
        CONTAINER_SIMPLE_PARAMS="${CONTAINER_INTERACTIVE} ${CI_PARAMS} --rm -v ${ROOT_DIR}:${ROOT_DIR}:Z -w ${ROOT_DIR}"
        DOCUMENTATION_COMMON_PARAMS="${CONTAINER_INTERACTIVE} ${CI_PARAMS} --rm -v ${ROOT_DIR}:${ROOT_DIR}:Z -v ${ROOT_DIR}:/project:Z"
    else
        CONTAINER_COMMON_PARAMS="${CONTAINER_INTERACTIVE} ${CI_PARAMS} --rm --network ${NETWORK} -v ${ROOT_DIR}:${ROOT_DIR} -w ${ROOT_DIR}"
        CONTAINER_SIMPLE_PARAMS="${CONTAINER_INTERACTIVE} ${CI_PARAMS} --rm -v ${ROOT_DIR}:${ROOT_DIR} -w ${ROOT_DIR}"
        DOCUMENTATION_COMMON_PARAMS="${CONTAINER_INTERACTIVE} ${CI_PARAMS} --rm -v ${ROOT_DIR}:${ROOT_DIR} -v ${ROOT_DIR}:/project"
    fi
fi

if [ ${PHP_XDEBUG_ON} -eq 0 ]; then
    XDEBUG_MODE="-e XDEBUG_MODE=off"
    XDEBUG_CONFIG=" "
else
    XDEBUG_MODE="-e XDEBUG_MODE=debug -e XDEBUG_TRIGGER=foo"
    XDEBUG_CONFIG="client_port=${PHP_XDEBUG_PORT} client_host=${CONTAINER_HOST}"
fi

# Suite execution
case ${TEST_SUITE} in
    cgl)
        if [ "${CGLCHECK_DRY_RUN}" -eq 1 ]; then
            COMMAND="php -dxdebug.mode=off .Build/bin/php-cs-fixer fix --config Build/php-cs-fixer/php-cs-rules.php -v --dry-run --using-cache no --diff"
        else
            COMMAND="php -dxdebug.mode=off .Build/bin/php-cs-fixer fix --config Build/php-cs-fixer/php-cs-rules.php --using-cache no"
        fi
        ${CONTAINER_BIN} run ${CONTAINER_COMMON_PARAMS} --name cgl-${SUFFIX} -e COMPOSER_CACHE_DIR=.cache/composer -e COMPOSER_ROOT_VERSION=${COMPOSER_ROOT_VERSION} ${COMPOSER_AUTH_ENV} ${IMAGE_PHP} /bin/sh -c "${COMMAND}"
        SUITE_EXIT_CODE=$?
        ;;
    checkBom)
        COMMAND="Build/Scripts/checkUtf8Bom.sh"
        ${CONTAINER_BIN} run ${CONTAINER_COMMON_PARAMS} --name checkbom-${SUFFIX} -e COMPOSER_CACHE_DIR=.cache/composer -e COMPOSER_ROOT_VERSION=${COMPOSER_ROOT_VERSION} ${COMPOSER_AUTH_ENV} ${IMAGE_PHP} /bin/sh -c "${COMMAND}"
        SUITE_EXIT_CODE=$?
        ;;
    checkRst)
        if [[ -d "Documentation" ]]; then
            COMMAND="php -dxdebug.mode=off Build/Scripts/validateRstFiles.php"
            ${CONTAINER_BIN} run ${CONTAINER_COMMON_PARAMS} --name checkrst-${SUFFIX} -e COMPOSER_CACHE_DIR=.cache/composer -e COMPOSER_ROOT_VERSION=${COMPOSER_ROOT_VERSION} ${COMPOSER_AUTH_ENV} ${IMAGE_PHP} /bin/sh -c "${COMMAND}"
            SUITE_EXIT_CODE=$?
        else
            echo ">> No documentation. Skip check."
            SUITE_EXIT_CODE=0
        fi
        ;;
    checkExceptionCodes)
        COMMAND="Build/Scripts/duplicateExceptionCodeCheck.sh"
        ${CONTAINER_BIN} run ${CONTAINER_COMMON_PARAMS} --name checkexceptioncodes-${SUFFIX} -e COMPOSER_CACHE_DIR=.cache/composer -e COMPOSER_ROOT_VERSION=${COMPOSER_ROOT_VERSION} ${COMPOSER_AUTH_ENV} ${IMAGE_PHP} /bin/sh -c "${COMMAND}"
        SUITE_EXIT_CODE=$?
        ;;
    checkTestMethodsPrefix)
        COMMAND="php -dxdebug.mode=off Build/Scripts/testMethodPrefixChecker.php"
        ${CONTAINER_BIN} run ${CONTAINER_COMMON_PARAMS} --name checktestmethodsprefix-${SUFFIX} -e COMPOSER_CACHE_DIR=.cache/composer -e COMPOSER_ROOT_VERSION=${COMPOSER_ROOT_VERSION} ${COMPOSER_AUTH_ENV} ${IMAGE_PHP} /bin/sh -c "${COMMAND}"
        SUITE_EXIT_CODE=$?
        ;;
    clean)
        cleanCacheFiles
        cleanRenderedDocumentationFiles
        cleanTestFiles
        ;;
    cleanCache)
        cleanCacheFiles
        ;;
    cleanRenderedDocumentation)
        cleanRenderedDocumentationFiles
        ;;
    cleanTests)
        cleanTestFiles
        ;;
    composer)
        COMMAND=(composer "$@")
        ${CONTAINER_BIN} run ${CONTAINER_SIMPLE_PARAMS} --name composer-command-${SUFFIX} -e COMPOSER_CACHE_DIR=.cache/composer -e COMPOSER_ROOT_VERSION=${COMPOSER_ROOT_VERSION} ${COMPOSER_AUTH_ENV} ${IMAGE_PHP} "${COMMAND[@]}"
        SUITE_EXIT_CODE=$?
        ;;
    composerUpdate)
        rm -rf .Build composer.lock composer.json.orig
        \cp -f composer.json composer.json.orig
        ${CONTAINER_BIN} run ${CONTAINER_SIMPLE_PARAMS} --name composer-command-${SUFFIX} -e COMPOSER_CACHE_DIR=.cache/composer -e COMPOSER_ROOT_VERSION=${COMPOSER_ROOT_VERSION} ${COMPOSER_AUTH_ENV} ${IMAGE_PHP} composer require --dev --no-update "typo3/minimal":"^${CORE_VERSION}"
        SUITE_EXIT_CODE=$?
        if [[ "${SUITE_EXIT_CODE}" -eq 0 ]]; then
          ${CONTAINER_BIN} run ${CONTAINER_SIMPLE_PARAMS} --name composer-command-${SUFFIX} -e COMPOSER_CACHE_DIR=.cache/composer -e COMPOSER_ROOT_VERSION=${COMPOSER_ROOT_VERSION} ${COMPOSER_AUTH_ENV} ${IMAGE_PHP} composer install
          SUITE_EXIT_CODE=$?
        fi
        [[ -f composer.json.orig ]] && \cp -f composer.json.orig composer.json
        ;;
    functional)
        PHPUNIT_CONFIG_FILE="Build/phpunit/FunctionalTests.xml"
        COMMAND=(.Build/bin/phpunit -c ${PHPUNIT_CONFIG_FILE} --exclude-group not-${DBMS} "$@")
        case ${DBMS} in
            mariadb)
                echo "Using driver: ${DATABASE_DRIVER}"
                ensureImage "${IMAGE_MARIADB}"
                SUITE_EXIT_CODE=$? && [[ "${SUITE_EXIT_CODE}" -ne 0 ]] && printSummary
                ${CONTAINER_BIN} run --name mariadb-func-${SUFFIX} --network ${NETWORK} -d -e MYSQL_ROOT_PASSWORD=funcp --tmpfs /var/lib/mysql/:rw,noexec,nosuid ${IMAGE_MARIADB} ${MYSQL_SERVER_OPTIONS} >/dev/null
                SUITE_EXIT_CODE=$? && [[ "${SUITE_EXIT_CODE}" -ne 0 ]] && printSummary
                waitFor mariadb-func-${SUFFIX} 3306
                CONTAINERPARAMS="-e typo3DatabaseDriver=${DATABASE_DRIVER} -e typo3DatabaseName=func_test -e typo3DatabaseUsername=root -e typo3DatabaseHost=mariadb-func-${SUFFIX} -e typo3DatabasePassword=funcp"
                ${CONTAINER_BIN} run ${CONTAINER_COMMON_PARAMS} --name functional-${SUFFIX} ${XDEBUG_MODE} -e XDEBUG_CONFIG="${XDEBUG_CONFIG}" ${CONTAINERPARAMS} ${IMAGE_PHP} "${COMMAND[@]}"
                SUITE_EXIT_CODE=$?
                ;;
            mysql)
                echo "Using driver: ${DATABASE_DRIVER}"
                ensureImage "${IMAGE_MYSQL}"
                SUITE_EXIT_CODE=$? && [[ "${SUITE_EXIT_CODE}" -ne 0 ]] && printSummary
                ${CONTAINER_BIN} run --name mysql-func-${SUFFIX} --network ${NETWORK} -d -e MYSQL_ROOT_PASSWORD=funcp --tmpfs /var/lib/mysql/:rw,noexec,nosuid ${IMAGE_MYSQL} ${MYSQL_SERVER_OPTIONS} >/dev/null
                SUITE_EXIT_CODE=$? && [[ "${SUITE_EXIT_CODE}" -ne 0 ]] && printSummary
                waitFor mysql-func-${SUFFIX} 3306
                CONTAINERPARAMS="-e typo3DatabaseDriver=${DATABASE_DRIVER} -e typo3DatabaseName=func_test -e typo3DatabaseUsername=root -e typo3DatabaseHost=mysql-func-${SUFFIX} -e typo3DatabasePassword=funcp"
                ${CONTAINER_BIN} run ${CONTAINER_COMMON_PARAMS} --name functional-${SUFFIX} ${XDEBUG_MODE} -e XDEBUG_CONFIG="${XDEBUG_CONFIG}" ${CONTAINERPARAMS} ${IMAGE_PHP} "${COMMAND[@]}"
                SUITE_EXIT_CODE=$?
                ;;
            postgres)
                ensureImage "${IMAGE_POSTGRES}"
                SUITE_EXIT_CODE=$? && [[ "${SUITE_EXIT_CODE}" -ne 0 ]] && printSummary
                ${CONTAINER_BIN} run --name postgres-func-${SUFFIX} --network ${NETWORK} -d -e POSTGRES_PASSWORD=funcp -e POSTGRES_USER=funcu --tmpfs /var/lib/postgresql/data:rw,noexec,nosuid ${IMAGE_POSTGRES} >/dev/null
                SUITE_EXIT_CODE=$? && [[ "${SUITE_EXIT_CODE}" -ne 0 ]] && printSummary
                waitFor postgres-func-${SUFFIX} 5432
                CONTAINERPARAMS="-e typo3DatabaseDriver=pdo_pgsql -e typo3DatabaseName=bamboo -e typo3DatabaseUsername=funcu -e typo3DatabaseHost=postgres-func-${SUFFIX} -e typo3DatabasePassword=funcp"
                ${CONTAINER_BIN} run ${CONTAINER_COMMON_PARAMS} --name functional-${SUFFIX} ${XDEBUG_MODE} -e XDEBUG_CONFIG="${XDEBUG_CONFIG}" ${CONTAINERPARAMS} ${IMAGE_PHP} "${COMMAND[@]}"
                SUITE_EXIT_CODE=$?
                ;;
            sqlite)
                # create sqlite tmpfs mount typo3temp/var/tests/functional-sqlite-dbs/ to avoid permission issues
                mkdir -p "${ROOT_DIR}/.Build/Web/typo3temp/var/tests/functional-sqlite-dbs/"
                # "${TMPFS_MOUNT_OPTIONS}" carries the owner and mode the mount needs, which
                # differ per container binary - see where it is assigned. Without them the
                # test databases cannot be created and every test fails with "unable to open
                # database file".
                CONTAINERPARAMS="-e typo3DatabaseDriver=pdo_sqlite --tmpfs ${ROOT_DIR}/.Build/Web/typo3temp/var/tests/functional-sqlite-dbs/:${TMPFS_MOUNT_OPTIONS}"
                ${CONTAINER_BIN} run ${CONTAINER_COMMON_PARAMS} --name functional-${SUFFIX} ${XDEBUG_MODE} -e XDEBUG_CONFIG="${XDEBUG_CONFIG}" ${CONTAINERPARAMS} ${IMAGE_PHP} "${COMMAND[@]}"
                SUITE_EXIT_CODE=$?
                ;;
        esac
        ;;
    lintPhp)
        COMMAND="find . -name \\*.php ! -path "./.Build/\\*" ! -path "./.cache/\\*" -print0 | xargs -0 -n1 -P4 php -dxdebug.mode=off -l >/dev/null"
        ${CONTAINER_BIN} run ${CONTAINER_COMMON_PARAMS} --name lint-php-${SUFFIX} -e COMPOSER_CACHE_DIR=.cache/composer -e COMPOSER_ROOT_VERSION=${COMPOSER_ROOT_VERSION} ${COMPOSER_AUTH_ENV} ${IMAGE_PHP} /bin/sh -c "${COMMAND}"
        SUITE_EXIT_CODE=$?
        ;;
    lintTypoScript)
        COMMAND="php -dxdebug.mode=off .Build/bin/typoscript-lint --ansi --config=./Build/typoscript-lint/typoscript-lint.yml"
        ${CONTAINER_BIN} run ${CONTAINER_COMMON_PARAMS} --name lint-typoscript-${SUFFIX} -e COMPOSER_CACHE_DIR=.cache/composer -e COMPOSER_ROOT_VERSION=${COMPOSER_ROOT_VERSION} ${COMPOSER_AUTH_ENV} ${IMAGE_PHP} /bin/sh -c "${COMMAND}"
        SUITE_EXIT_CODE=$?
        ;;
    renderDocumentation)
        if ! pullRenderer "${IMAGE_DOCS}"; then
            SUITE_EXIT_CODE=1
        else
            ${CONTAINER_BIN} run ${DOCUMENTATION_COMMON_PARAMS} ${IMAGE_DOCS} --config=Documentation
            SUITE_EXIT_CODE=$?
        fi
        ;;
    phpstan)
        PHPSTAN_CONFIG_FILE="Build/phpstan/Core${CORE_VERSION}/phpstan.neon"
        COMMAND=(php -dxdebug.mode=off .Build/bin/phpstan analyse -c ${PHPSTAN_CONFIG_FILE} --no-progress --no-interaction --memory-limit 4G "$@")
        ${CONTAINER_BIN} run ${CONTAINER_COMMON_PARAMS} --name phpstan-${SUFFIX} -e COMPOSER_CACHE_DIR=.cache/composer -e COMPOSER_ROOT_VERSION=${COMPOSER_ROOT_VERSION} ${COMPOSER_AUTH_ENV} ${IMAGE_PHP} "${COMMAND[@]}"
        SUITE_EXIT_CODE=$?
        ;;
    phpstanGenerateBaseline)
        PHPSTAN_CONFIG_FILE="Build/phpstan/Core${CORE_VERSION}/phpstan.neon"
        COMMAND=(php -dxdebug.mode=off .Build/bin/phpstan analyse -c ${PHPSTAN_CONFIG_FILE} --no-progress --no-interaction --memory-limit 4G --allow-empty-baseline --generate-baseline=Build/phpstan/Core${CORE_VERSION}/phpstan-baseline.neon)
        ${CONTAINER_BIN} run ${CONTAINER_COMMON_PARAMS} --name phpstan-baseline-${SUFFIX} -e COMPOSER_CACHE_DIR=.cache/composer -e COMPOSER_ROOT_VERSION=${COMPOSER_ROOT_VERSION} ${COMPOSER_AUTH_ENV} ${IMAGE_PHP} "${COMMAND[@]}"
        SUITE_EXIT_CODE=$?
        ;;
    unit)
        PHPUNIT_CONFIG_FILE="Build/phpunit/UnitTests.xml"
        COMMAND=(.Build/bin/phpunit -c ${PHPUNIT_CONFIG_FILE} "$@")
        ${CONTAINER_BIN} run ${CONTAINER_COMMON_PARAMS} --name unit-${SUFFIX} ${XDEBUG_MODE} -e XDEBUG_CONFIG="${XDEBUG_CONFIG}" ${IMAGE_PHP} "${COMMAND[@]}"
        SUITE_EXIT_CODE=$?
        ;;
    unitRandom)
        PHPUNIT_CONFIG_FILE="Build/phpunit/UnitTests.xml"
        COMMAND=(.Build/bin/phpunit -c ${PHPUNIT_CONFIG_FILE} --order-by=random ${PHPUNIT_RANDOM} "$@")
        ${CONTAINER_BIN} run ${CONTAINER_COMMON_PARAMS} --name unit-random-${SUFFIX} ${XDEBUG_MODE} -e XDEBUG_CONFIG="${XDEBUG_CONFIG}" ${IMAGE_PHP} "${COMMAND[@]}"
        SUITE_EXIT_CODE=$?
        ;;
    update)
        # pull typo3/core-testing-* versions of those ones that exist locally
        echo "> pull ghcr.io/typo3/core-testing-* versions of those ones that exist locally"
        ${CONTAINER_BIN} images "ghcr.io/typo3/core-testing-*" --format "{{.Repository}}:{{.Tag}}" | xargs -I {} ${CONTAINER_BIN} pull {}
        echo ""
        # remove "dangling" typo3/core-testing-* images (those tagged as <none>)
        echo "> remove \"dangling\" ghcr.io/typo3/core-testing-* images (those tagged as <none>)"
        ${CONTAINER_BIN} images --filter "reference=ghcr.io/typo3/core-testing-*" --filter "dangling=true" --format "{{.ID}}" | xargs -I {} ${CONTAINER_BIN} rmi -f {}
        echo ""
        # pull ghcr.io/web-vision/ versions of those ones that exist locally
        echo "> pull ghcr.io/web-vision/* versions of those ones that exist locally"
        ${CONTAINER_BIN} images "ghcr.io/web-vision/*" --format "{{.Repository}}:{{.Tag}}" | xargs -I {} ${CONTAINER_BIN} pull {}
        echo ""
        # remove "dangling" ghcr.io/web-vision/ images (those tagged as <none>)
        echo "> remove \"dangling\" ghcr.io/web-vision/* images (those tagged as <none>)"
        ${CONTAINER_BIN} images --filter "reference=ghcr.io/web-vision/*" --filter "dangling=true" --format "{{.ID}}" | xargs -I {} ${CONTAINER_BIN} rmi -f {}
        echo ""
        ;;
    help)
        cleanUp
        loadHelp
        echo "${HELP}" >&2
        exit 0
        ;;
    *)
        loadHelp
        echo "Invalid -s option argument ${TEST_SUITE}" >&2
        echo >&2
        echo "${HELP}" >&2
        exit 1
        ;;
esac

# Cleanup, print summary && exit with exitcode
printSummary
