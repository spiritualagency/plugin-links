const PLUGIN_API_ENDPOINT = '/wp-json/plugin-links/v1/plugins';

class PluginManager {
  constructor() {
    this.plugins = [];
    this.init();
  }

  async init() {
    try {
      await this.fetchPluginList();
    } catch (error) {
      this.showError('Failed to initialize PluginManager');
    }
  }

  async fetchPluginList() {
    try {
      const response = await fetch(PLUGIN_API_ENDPOINT);
      if (!response.ok) throw new Error('Failed to fetch plugin list');
      const data = await response.json();
      this.plugins = Array.isArray(data) ? data : [];
      this.validatePlugins();
    } catch (error) {
      this.showError('Error fetching plugin list');
    }
  }

  validatePlugins() {
    this.plugins = this.plugins.filter(plugin =>
      plugin && typeof plugin.name === 'string' && typeof plugin.description === 'string'
    );
  }

  searchPlugins(query) {
    if (!query) return this.plugins;
    query = query.toLowerCase();
    return this.plugins.filter(plugin =>
      plugin.name.toLowerCase().includes(query) ||
      plugin.description.toLowerCase().includes(query)
    );
  }

  filterPlugins(criteria) {
    return this.plugins.filter(plugin =>
      Object.keys(criteria).every(key => plugin[key] === criteria[key])
    );
  }

  async createZipFile(pluginIds) {
    try {
      const response = await fetch(`${PLUGIN_API_ENDPOINT}/zip`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({ pluginIds })
      });
      if (!response.ok) throw new Error('Failed to create zip file');
      const blob = await response.blob();
      this.downloadBlob(blob, 'plugins.zip');
    } catch (error) {
      this.showError('Error creating zip file');
    }
  }

  downloadBlob(blob, filename) {
    const link = document.createElement('a');
    link.href = window.URL.createObjectURL(blob);
    link.download = filename;
    link.click();
    link.remove();
  }

  showError(message) {
    console.error(message);
    // Assuming there's a div with the id 'error-message' to display UI errors.
    const errorMessageElement = document.getElementById('error-message');
    if (errorMessageElement) {
      errorMessageElement.textContent = message;
      errorMessageElement.style.display = 'block';
    }
  }
}

// Usage example:
// const pluginManager = new PluginManager();
// const searchedPlugins = pluginManager.searchPlugins('security');
// const filteredPlugins = pluginManager.filterPlugins({ active: true });
// pluginManager.createZipFile([1, 2, 3]);