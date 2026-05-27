// Fix for enrolment screen JSON parse errors
// Add to js/screens.js load functions

function safeJsonParse(response) {
  return response.text().then(text => {
    try {
      return JSON.parse(text);
    } catch (e) {
      console.error('JSON parse failed:', text.slice(0, 200));
      return [];
    }
  });
}

// Update load functions:
function loadWorkmen() {
  fetch('get_worker.php')
    .then(safeJsonParse)
    .then(data => {
      workmenData = data;
      updateStats();
      renderWorkmenTable();
    })
    .catch(error => {
      console.error('Error loading workmen:', error);
      workmenData = [];
      updateStats();
    });
}

// Same for supervisors/representatives

// Run in console on enrolment screen:
loadWorkmen();
loadSupervisors();
loadRepresentatives();

