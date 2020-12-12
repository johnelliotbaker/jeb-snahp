from pathlib import Path
from functools import wraps
import os

TARGET_CLASSNAMES = [
    "Xmas",
    "Board",
]
TARGET_VARNAMES = [
    "xmas",
    "xmas",
    "board",
    "board",
]

PLACEHOLDER_CLASSNAMES = ["Boilerplate", "ModelName"]
PLACEHOLDER_VARNAMES = ["boilerplate_", "boilerplate-", "modelname_", "modelname-"]


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
    for (index, placeholder) in enumerate(PLACEHOLDER_CLASSNAMES):
        target_name = TARGET_CLASSNAMES[index]
        rename_modules(placeholder, target_name)
        rename_variables(placeholder, target_name)

    for (index, placeholder) in enumerate(PLACEHOLDER_VARNAMES):
        target_name = TARGET_VARNAMES[index]
        rename_variables(placeholder, target_name)


if __name__ == "__main__":
    import doctest

    doctest.testmod()
    main()
