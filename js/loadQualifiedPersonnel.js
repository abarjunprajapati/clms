// Global loader for qualified personnel
async function loadQualifiedPersonnel(callback) {
  try {
    const parsed = await apiFetch('get_gatepass_personnel.php');
    console.log("[loadQualifiedPersonnel] response:", parsed);
    if (parsed.success && parsed.data) {
      const list = parsed.data.data || parsed.data || [];
      if (APP_DATA) {
        APP_DATA.qualifiedPersonnel = list;
        APP_DATA.workmen = list; // for backward compatibility
      }
    } else {
      if (APP_DATA) {
        APP_DATA.qualifiedPersonnel = [];
        APP_DATA.workmen = [];
      }
    }
  } catch (err) {
    console.error('[loadQualifiedPersonnel] error:', err);
    if (APP_DATA) {
      APP_DATA.qualifiedPersonnel = [];
      APP_DATA.workmen = [];
    }
  }
  if (callback) callback();
}


