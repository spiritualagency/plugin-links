const SUCCESS_STATUS = "success";
const ERROR_STATUS = "error";

// Enhance UI function to modify interface elements with configurable options
function enhanceUI(options = {}) {
  const elementsToEnhance = document.querySelectorAll('.enhance-me');
  elementsToEnhance.forEach(element => {
    element.style.transition = options.transition || "all 0.3s ease-in-out";
    element.style.opacity = options.opacity || "1";
    if (options.additionalStyles) {
      Object.assign(element.style, options.additionalStyles);
    }
  });
}

// Function to display status messages with class preservation
function displayStatus(message, type = SUCCESS_STATUS) {
  const statusElement = document.getElementById('status-message');
  if (statusElement) {
    const baseClasses = Array.from(statusElement.classList).filter(cls => !cls.startsWith('status-'));
    statusElement.className = baseClasses.join(' ');
    statusElement.classList.add(type === SUCCESS_STATUS ? 'status-success' : 'status-error');
    statusElement.textContent = message;
  }
}

// Enhanced error handling function
function handleError(error) {
  console.error("An error occurred:", error.message, { code: error.code, meta: error.metadata });
  if (typeof sendErrorDetailsToMonitoringService === 'function') {
    sendErrorDetailsToMonitoringService(error);
  }
  displayStatus("An unexpected error has occurred. Please try again.", ERROR_STATUS);
}