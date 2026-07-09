import os

file_path = r"C:\xampp\htdocs\CLMS1\pages\contractor\annexure-2a.php"
ui_path = r"C:\xampp\htdocs\CLMS1\pages\contractor\temp_new_ui.html"

with open(file_path, 'r', encoding='utf-8') as f:
    lines = f.readlines()

with open(ui_path, 'r', encoding='utf-8') as f:
    new_ui = f.read()

start_idx = -1
end_idx = -1

for i, line in enumerate(lines):
    if "<!-- ================= REGISTRATION TAB ================= -->" in line and start_idx == -1:
        start_idx = i
    if "<?php endif; ?>" in line and start_idx != -1 and end_idx == -1:
        # Check if the line above had sticky-bottom-bar (well, it's a few lines above)
        # We know there's only one <?php endif; ?> in this section.
        end_idx = i

if start_idx != -1 and end_idx != -1:
    new_lines = lines[:start_idx] + [new_ui + "\n"] + lines[end_idx+1:]
    with open(file_path, 'w', encoding='utf-8') as f:
        f.writelines(new_lines)
    print(f"Successfully replaced UI from line {start_idx} to {end_idx}")
else:
    print(f"Failed to find bounds: {start_idx} to {end_idx}")
