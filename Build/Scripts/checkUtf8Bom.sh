#!/usr/bin/env bash

#########################
#
# Check all UTF-8 files tracked by git do not contain BOM.
#
# It expects to be run from the extension root.
#
##########################

if ! git ls-files > /dev/null; then
    echo "Could not list the files tracked by git.";
    exit 1;
fi

FILES=`git ls-files -z \
    | xargs -0 -r -n1 -P8 file \
    | grep -E 'UTF-8 Unicode \(with BOM\)|UTF-8 \(with BOM\)'`

if [ -n "${FILES}" ]; then
    echo "Found UTF-8 files with BOM:";
    echo ${FILES};
    exit 1;
fi

exit 0
