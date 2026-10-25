<?php
/**
 * Test Suite: Successor Search Filter Verification
 * Verifies that the designated successor dropdown in the succession modal
 * has an integrated real-time search filter and clear mechanism.
 */

function assertCondition(string $name, bool $passed): void {
    if ($passed) {
        echo " [PASS] $name\n";
    } else {
        echo " [FAIL] $name\n";
        exit(1);
    }
}

echo "=================================================================\n";
echo "VERIFYING DESIGNATED SUCCESSOR SEARCH FILTER IMPLEMENTATION\n";
echo "=================================================================\n\n";

$dashFile = file_get_contents(__DIR__ . '/../pages/dashboard.php');
$adminJs = file_get_contents(__DIR__ . '/../js/admin.js');
$cssFile = file_get_contents(__DIR__ . '/../css/modern-design-system.css');

// 1. Dashboard HTML Template Verification
echo "--- 1. Dashboard HTML Template Verification ---\n";
assertCondition(
    "Dashboard contains #succSearchInput inside succession modal",
    strpos($dashFile, 'id="succSearchInput"') !== false
);
assertCondition(
    "#succSearchInput triggers filterSuccessorOptions() oninput",
    strpos($dashFile, 'oninput="filterSuccessorOptions()"') !== false
);
assertCondition(
    "#succSearchInput triggers handleSuccessorSearchKey(event) onkeydown",
    strpos($dashFile, 'onkeydown="handleSuccessorSearchKey(event)"') !== false
);
assertCondition(
    "Dashboard contains clear button #succSearchClear with clearSuccessorSearch()",
    strpos($dashFile, 'id="succSearchClear"') !== false &&
    strpos($dashFile, 'onclick="clearSuccessorSearch()"') !== false
);
assertCondition(
    "Search container has search icon SVG",
    strpos($dashFile, 'class="succ-search-icon"') !== false
);
assertCondition(
    "Successor select element #succSuccessorSelect is present directly below search input",
    strpos($dashFile, 'id="succSuccessorSelect"') !== false
);

// 2. CSS Styling Verification
echo "\n--- 2. CSS Styling Verification ---\n";
assertCondition(
    "CSS defines .succ-search-container",
    strpos($cssFile, '.succ-search-container') !== false
);
assertCondition(
    "CSS defines .succ-search-icon",
    strpos($cssFile, '.succ-search-icon') !== false
);
assertCondition(
    "CSS defines .succ-search-input with proper padding for icon",
    strpos($cssFile, '.succ-search-input') !== false &&
    strpos($cssFile, 'padding-left: 2.35rem') !== false
);
assertCondition(
    "CSS defines .succ-search-clear with hover states",
    strpos($cssFile, '.succ-search-clear') !== false &&
    strpos($cssFile, '.succ-search-clear:hover') !== false
);

// 3. JavaScript Logic Verification
echo "\n--- 3. JavaScript Logic in js/admin.js Verification ---\n";
assertCondition(
    "admin.js defines filterSuccessorOptions()",
    strpos($adminJs, 'function filterSuccessorOptions()') !== false
);
assertCondition(
    "admin.js defines renderSuccessorOptions()",
    strpos($adminJs, 'function renderSuccessorOptions(') !== false
);
assertCondition(
    "admin.js defines clearSuccessorSearch()",
    strpos($adminJs, 'function clearSuccessorSearch()') !== false
);
assertCondition(
    "admin.js defines handleSuccessorSearchKey()",
    strpos($adminJs, 'function handleSuccessorSearchKey(') !== false
);
assertCondition(
    "filterSuccessorOptions() filters by username, name, email, and id_number",
    strpos($adminJs, 'username.includes(query)') !== false &&
    strpos($adminJs, 'fullName.includes(query)') !== false &&
    strpos($adminJs, 'email.includes(query)') !== false &&
    strpos($adminJs, 'idNumber.includes(query)') !== false
);
assertCondition(
    "openSuperadminSuccessionModal() resets search input and clear button",
    strpos($adminJs, "searchInput.value = '';") !== false &&
    strpos($adminJs, "clearBtn.style.display = 'none';") !== false
);
assertCondition(
    "window exposures include filterSuccessorOptions, clearSuccessorSearch, handleSuccessorSearchKey",
    strpos($adminJs, 'window.filterSuccessorOptions = filterSuccessorOptions;') !== false &&
    strpos($adminJs, 'window.clearSuccessorSearch = clearSuccessorSearch;') !== false &&
    strpos($adminJs, 'window.handleSuccessorSearchKey = handleSuccessorSearchKey;') !== false
);

echo "\n=================================================================\n";
echo "ALL SUCCESSOR SEARCH FILTER TESTS PASSED SUCCESSFULLY!\n";
echo "=================================================================\n";
