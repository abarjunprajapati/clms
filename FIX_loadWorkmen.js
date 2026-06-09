/**
 * FIXED loadWorkmen() function for Contractor Management System
 * 
 * PROBLEM: Table shows "No workmen found" even though API returns data correctly
 * ROOT CAUSE: 
 *   1. Function returns early when appId is null, setting empty array
 *   2. URL was built with null application_id parameter
 *   3. No handling for nested data structures (json.data.data)
 * 
 * FIX: 
 *   - Remove early return when appId is null (API supports fetching ALL workmen)
 *   - Build URL conditionally to avoid null parameter
 *   - Handle multiple data extraction patterns
 *   - Add comprehensive debug logging
 */

async function loadWorkmen() {
  const appId = getAppId();
  console.log('[loadWorkmen] Loading with application_id:', appId);
  
  // FIX: Build URL conditionally - don't send null application_id
  let endpoint = 'get_workmen.php';
  if (appId) {
    endpoint += '?application_id=' + encodeURIComponent(appId);
  }
  // If no appId, fetch ALL workmen (API supports this)
  
  try {
    const result = await apiFetch(endpoint);
    console.log('[loadWorkmen] Raw API response:', result);
    
    // FIX: Correct data extraction with optional chaining and null safety
    // API returns: { success: true, data: [...], counts: {...} }
    let data = [];
    if (result?.success) {
      if (Array.isArray(result?.data)) {
        // Direct array case: { success: true, data: [...] }
        data = result.data;
      } else if (result?.data?.data && Array.isArray(result.data.data)) {
        // Nested case (some APIs wrap data): { success: true, data: { data: [...] } }
        // This handles json.data.data anti-pattern
        data = result.data.data;
        console.warn('[loadWorkmen] Detected nested data - using result.data.data');
      } else if (result?.data && typeof result.data === 'object') {
        // Try to find array inside object
        const arr = Object.values(result.data).find(v => Array.isArray(v));
        data = arr || [];
        console.log('[loadWorkmen] Extracted array from object:', arr?.length || 0);
      }
    }
    
    // Debug logging
    console.log('[loadWorkmen] Extracted data array:', data?.length || 0, data?.slice(0, 2));
    
    // Handle empty/null safely
    if (!Array.isArray(data)) {
      console.warn('[loadWorkmen] data is not an array, resetting to empty:', typeof data);
      data = [];
    }
    
    data = data || [];
    console.log('[loadWorkmen] Final data count:', data.length);
    
    // Update global state
    workmanship = data;
    updateStats();
    renderWorkmenTable();
    
  } catch (error) {
    console.error('[loadWorkmen] Error:', error);
    workmanship = [];
    updateStats();
    renderWorkmenTable();
  }
}
