"""
FILE:      tests/e2e/guard.py
CREATED:   2026-11-08 08:34 +03
TASK:      whmcs-e2e
AUTHOR:    M Alhassoun
WHAT:      Adds a send guard to a TEST BUILD of lib/SmsHelper.php: the send throws unless test mode is on and the
           only recipient is KWTSMS_E2E_TEST_NUMBER (container env). Never applied to the repo or the release zip.
WHY:       Proves test mode by refusing, not by reading logs afterwards: no test can reach any other handset.
USAGE:     python3 guard.py <path/to/SmsHelper.php>
DEPENDS:   Python 3 standard library.
"""
import os, sys

path = sys.argv[1]
src = open(path).read()
anchor = "        $phoneStr = implode(',', $covered);"
if src.count(anchor) != 1:
    sys.exit("guard: anchor not found in " + path)
guard = (anchor + "\n        if (!$testMode || $phoneStr !== (string) getenv('KWTSMS_E2E_TEST_NUMBER')) {"
         " throw new \\RuntimeException('E2E GUARD: refused a send that is not test mode to the test number'); }\n")
tmp = path + ".tmp"
open(tmp, "w").write(src.replace(anchor, guard))
if os.path.getsize(tmp) < len(src):
    sys.exit("guard: write failed")
os.replace(tmp, path)
print("guard added")
