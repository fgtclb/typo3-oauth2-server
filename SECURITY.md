# Security Policy

This product ("fgtclb/typo3-oauth2-server") is developed and maintained by FGTCLB GmbH.
As a manufacturer of a product with digital elements under the EU Cyber
Resilience Act (Regulation (EU) 2024/2847), we are committed to identifying,
assessing, and remediating security vulnerabilities in this product in a
timely manner, and to coordinating responsibly with security researchers.

## Supported Versions

Security updates are provided for the following versions. Versions marked
unsupported no longer receive security fixes; please upgrade before
reporting an issue against them.

| Version | Supported |
| ------- | --------- |
| 2.x     | :x:       |
| 1.x     | :x:       |
| < 1.0   | :x:       |

> **Note:** the newest version of this product targets a TYPO3 version that has left
> regular LTS maintenance, so no version is currently covered by security updates.
> Support for a newer TYPO3 version may be added in the future; if you depend on
> this product, please get in touch through the reporting channel below.

Planned end of support for this product: **30 April 2026 (end of regular TYPO3 12 LTS support)**.

## Reporting a Vulnerability

Please report suspected security vulnerabilities privately — **do not**
open a public GitHub/GitLab issue.

- **Report here:** https://security.fgtclb.com (our secure incident report form)
- **Please include:** affected version(s), a description of the issue,
  steps to reproduce or a proof of concept, and the potential impact.

### What to expect

| Step                                | Timeframe                                                              |
| ----------------------------------- | ---------------------------------------------------------------------- |
| Acknowledgement of your report      | within 1 business day (typically much faster)                          |
| Status updates                      | at least every 7 days until resolved                                   |
| Fix / mitigation, based on severity | Critical/High: as fast as possible; Medium/Low: next scheduled release |

We coordinate the disclosure timeline with the reporter and aim for a
resolution before any public disclosure. If you'd like credit for your
finding, let us know how you'd like to be named; we're also happy to keep
your report confidential if you prefer.

## Safe Harbor

We consider security research conducted in good faith, in accordance with
this policy, to be authorized. We will not pursue legal action against
researchers who:

- make a genuine effort to avoid privacy violations, data destruction, and
  service interruption during their research,
- report a vulnerability promptly and do not exploit it beyond what is
  necessary to demonstrate the issue,
- do not publicly disclose the vulnerability before we have had a
  reasonable opportunity to address it (see timeframes above).

## Scope

In scope: the source code, released versions, and official distribution
channels of "fgtclb/typo3-oauth2-server" (e.g. TER / Packagist).

Out of scope: third-party dependencies (please report those upstream, but
feel free to let us know so we can track and update them), and
vulnerabilities in the host application (TYPO3 core) itself
unless directly caused by this extension.

## Coordinator

Vulnerability reports for this product are handled by the
Vulnerability Coordinator team at FGTCLB GmbH,
under the oversight of our GRC/CISO function.
