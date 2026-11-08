"""
FILE:      tests/e2e/install_json.py
CREATED:   2026-11-08 08:34 +03
TASK:      whmcs-e2e
AUTHOR:    M Alhassoun
WHAT:      Prints the WHMCS CLI installer JSON (licence from the env file, random admin password saved to a file).
WHY:       Keeps the licence and passwords out of argv and the terminal.
USAGE:     python3 install_json.py <env_file> <admin_pass_out> > install.json
DEPENDS:   Python 3 standard library.
"""
import json, re, secrets, sys

env = {m.group(1): m.group(2).strip().strip('"\'') for line in open(sys.argv[1])
       for m in [re.match(r'^([A-Z_]+)=(.*)$', line.strip())] if m}
pw = 'E2e' + secrets.token_hex(8) + '!9'
with open(sys.argv[2], 'w') as f:
    f.write(pw)
print(json.dumps({
    "admin": {"username": "e2eadmin", "password": pw, "email": "e2e.admin@example.com"},
    "configuration": {"license": env['WHMCS_LICENSE'], "db_host": "db", "db_port": "", "db_username": "whmcs",
                      "db_password": "e2epass", "db_name": "whmcs", "cc_encryption_hash": secrets.token_hex(32),
                      "mysql_charset": "utf8mb4"}}))
