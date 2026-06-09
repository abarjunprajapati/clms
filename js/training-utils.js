// Safety Training Dynamic Functions
// =============================================

// Dynamic data cache
let trainingSessionsCache = [];
let trainingSessionsLoading = false;

// Fetch training sessions from API
async function fetchTrainingSessions() {
  if (trainingSessionsCache.length > 0 && !trainingSessionsLoading) {
    return trainingSessionsCache;
  }
  
  trainingSessionsLoading = true;
  
  try {
    const parsed = await apiFetch('get_training_sessions.php');
    console.log('[trainingUtils.fetchTrainingSessions] actual response structure:', parsed);
    trainingSessionsCache = normalizeArray(parsed);
  } catch (error) {
    console.error('[fetchTrainingSessions] error:', error);
    trainingSessionsCache = []; // fallback to empty
  } finally {
    trainingSessionsLoading = false;
  }
  
  return trainingSessionsCache;
}

// Fetch training results for specific session
async function fetchTrainingResults(sessionId) {
  try {
    const parsed = await apiFetch(`get_training_results.php?session_id=${encodeURIComponent(sessionId)}`);
    console.log('[trainingUtils.fetchTrainingResults] actual response structure:', parsed);
    return normalizeArray(parsed);
  } catch (error) {
    console.error('[fetchTrainingResults] error:', error);
    return [];
  }
}

// Clear cache
function clearTrainingCache() {
  trainingSessionsCache = [];
}

// Export for use in screens.js
window.trainingUtils = {
  fetchTrainingSessions,
  fetchTrainingResults,
  clearTrainingCache
};

