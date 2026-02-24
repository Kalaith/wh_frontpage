import subprocess
import sys
from pathlib import Path


def main():
    root_script = Path(__file__).resolve().parents[2] / "import_quests_to_github.py"
    if not root_script.exists():
        print(f"Error: Root importer not found at '{root_script}'")
        return 1

    cmd = [sys.executable, str(root_script)] + sys.argv[1:]
    result = subprocess.run(cmd)
    return result.returncode


if __name__ == "__main__":
    raise SystemExit(main())
