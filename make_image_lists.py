from pathlib import Path


DATA_DIR = Path("data")
OUTPUT_DIR = DATA_DIR / "text_info"
DATA_DIR_LIST_FILE = OUTPUT_DIR / "data_dir_list.txt"

IMAGE_EXTENSIONS = {".jpg", ".jpeg", ".png"}


def is_hidden_name(path: Path) -> bool:
    """
    Skip files/folders whose name starts with ".".
    This also skips macOS AppleDouble files like ._something.jpg.
    """
    return path.name.startswith(".")


def has_hidden_part(path: Path) -> bool:
    """
    Skip paths that contain any hidden file or folder component.
    """
    return any(part.startswith(".") for part in path.parts)


def find_images(folder: Path) -> list[Path]:
    """
    Recursively find non-hidden jpg/jpeg/png files inside one folder.
    """
    images = []

    for file_path in folder.rglob("*"):
        if not file_path.is_file():
            continue

        if has_hidden_part(file_path.relative_to(folder)):
            continue

        if file_path.suffix.lower() in IMAGE_EXTENSIONS:
            images.append(file_path)

    return sorted(images)


def main() -> None:
    if not DATA_DIR.exists():
        raise FileNotFoundError(f"Could not find data folder: {DATA_DIR.resolve()}")

    OUTPUT_DIR.mkdir(parents=True, exist_ok=True)

    # Rebuild this file each run so repeated runs do not create duplicate entries.
    DATA_DIR_LIST_FILE.write_text("", encoding="utf-8")

    for item in sorted(DATA_DIR.iterdir()):
        if not item.is_dir():
            continue

        if is_hidden_name(item):
            continue

        # Do not scan the output folder itself.
        if item == OUTPUT_DIR:
            continue

        images = find_images(item)

        # Skip folders that do not contain images.
        if not images:
            continue

        txt_file = OUTPUT_DIR / f"{item.name}.txt"

        with txt_file.open("w", encoding="utf-8") as f:
            for image_path in images:
                # Write paths relative to where the script is run.
                f.write(f"{image_path.as_posix()}\n")

        with DATA_DIR_LIST_FILE.open("a", encoding="utf-8") as f:
            f.write(f"{txt_file.as_posix()}\n")

        print(f"Created {txt_file} with {len(images)} image paths")

    print(f"Created {DATA_DIR_LIST_FILE}")


if __name__ == "__main__":
    main()
