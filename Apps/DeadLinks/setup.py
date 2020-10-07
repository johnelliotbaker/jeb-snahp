from pathlib import Path
from functools import wraps
import os

TARGET_CLASSNAME = "DeadLinks"
TARGET_VARNAME = "deadlinks"

PLACEHOLDER_CLASSNAME = "Wiki"
PLACEHOLDER_VARNAME = "wiki"


SCRIPT_CWD = Path(os.path.dirname(os.path.abspath(__file__)))


def cd(f):
    prev_cwd = Path.cwd()

    @wraps(f)
    def wrapper(*args, **kwargs):
        os.chdir(SCRIPT_CWD)
        res = f(*args, **kwargs)
        os.chdir(prev_cwd)
        return res

    return wrapper


def rename_modules(placeholder, new_name):
    files = Path(".").glob(f"{placeholder}*.php")
    for file in files:
        name = file.name
        name = name.replace(f"{placeholder}", new_name)
        file.rename(name)


def rename_variables(placeholder, new_name):
    files = Path(".").glob(f"*.(php|yml)")
    files = {
        p.resolve() for p in Path(".").glob("**/*") if p.suffix in [".php", ".yml"]
    }
    for file in files:
        with open(file, "r", encoding="utf8") as f:
            s = f.read()
        with open(file, "w", encoding="utf8") as f:
            f.write(s.replace(placeholder, new_name))


@cd
def main():
    rename_modules(PLACEHOLDER_CLASSNAME, TARGET_CLASSNAME)
    rename_variables(PLACEHOLDER_CLASSNAME, TARGET_CLASSNAME)
    rename_variables(PLACEHOLDER_VARNAME, TARGET_VARNAME)


if __name__ == "__main__":
    import doctest

    doctest.testmod()
    main()
