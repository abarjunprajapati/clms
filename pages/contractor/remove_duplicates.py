import sys

file_path = r"C:\xampp\htdocs\CLMS1\pages\contractor\annexure-2a.php"

with open(file_path, 'r', encoding='utf-8') as f:
    lines = f.readlines()

# We want to delete lines 1414 to 1760 (1-indexed), which is index 1413 to 1759.
# Let's double check by finding the exact markers just to be completely safe.
# The marker for start of deletion is right after the newly inserted Registration Tab's closing div.
# We know the new block ends with the sticky-bottom-bar and a closing </div>.
# Then the next line is 1415 which has `<button type="button" class="btn btn-sm btn-reg-draft" id="addEcpBtn"`.
# The marker for end of deletion is `<?php endif; ?>` just before `            </div>` and `        </div>` and `    </form>`.

start_idx = 1413 # 0-indexed for line 1414
end_idx = 1759 # 0-indexed for line 1760

# Let's verify the bounds
if "addEcpBtn" in lines[1414] and "<?php endif; ?>" in lines[1759] and "</form>" in lines[1762]:
    print("Exact line numbers match. Deleting...")
    new_lines = lines[:start_idx] + lines[end_idx+1:]
    with open(file_path, 'w', encoding='utf-8') as f:
        f.writelines(new_lines)
    print("Duplicates removed successfully.")
else:
    print(f"Line mismatch! 1415 is: {lines[1414]}")
    print(f"1760 is: {lines[1759]}")
    print(f"1763 is: {lines[1762]}")
    sys.exit(1)
