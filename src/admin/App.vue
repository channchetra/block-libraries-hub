<template>
  <div class="gcp-admin-wrap">
    <h1>Guten Cloud Private Settings</h1>
    
    <div class="gcp-settings-card">
      <div class="gcp-tabs">
        <button 
          v-for="tab in tabs" 
          :key="tab.id"
          :class="['gcp-tab-btn', { active: activeTab === tab.id }]"
          @click="activeTab = tab.id"
        >
          {{ tab.label }}
        </button>
      </div>

      <div class="gcp-tab-content">
        <!-- Github Settings -->
        <div v-if="activeTab === 'github'" class="gcp-settings-section">
          <div class="gcp-field">
            <label>Github Token</label>
            <input type="password" v-model="settings.github_token" placeholder="ghp_xxxxxxxxxxxx">
          </div>
          <div class="gcp-field">
            <label>Github Repository</label>
            <input type="text" v-model="settings.github_repo" placeholder="username/repo">
          </div>
          <div class="gcp-field">
            <label>Github Path</label>
            <input type="text" v-model="settings.github_path" placeholder="patterns">
          </div>
          <button class="button" @click="checkConnection('github')">Check Connection</button>
        </div>

        <!-- Google Drive Settings -->
        <div v-if="activeTab === 'gdrive'" class="gcp-settings-section">
          <div class="gcp-field">
            <label>Google Drive Folder ID</label>
            <input type="text" v-model="settings.gdrive_folder_id" placeholder="Folder ID">
          </div>
          <button class="button" @click="checkConnection('gdrive')">Check Connection</button>
        </div>

        <!-- Hosting Root Settings -->
        <div v-if="activeTab === 'local'" class="gcp-settings-section">
          <div class="gcp-field">
            <label>Hosting Root Path</label>
            <input type="text" v-model="settings.local_root" placeholder="/guten-library">
          </div>
          <button class="button" @click="checkConnection('local')">Check Connection</button>
        </div>
      </div>

      <div class="gcp-footer">
        <button class="button button-primary" @click="saveSettings" :disabled="isSaving">
          {{ isSaving ? 'Saving...' : 'Save Settings' }}
        </button>
      </div>
    </div>

    <!-- Modal for Alert -->
    <div v-if="modal.show" class="gcp-modal-overlay">
      <div class="gcp-modal">
        <h3>{{ modal.title }}</h3>
        <p>{{ modal.message }}</p>
        <button class="button" @click="modal.show = false">Close</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const tabs = [
  { id: 'github', label: 'Github' },
  { id: 'gdrive', label: 'Google Drive' },
  { id: 'local', label: 'Hosting Root' }
];

const activeTab = ref('github');
const isSaving = ref(false);
const settings = ref({
  github_token: '',
  github_repo: '',
  github_path: '',
  gdrive_folder_id: '',
  local_root: '/guten-library'
});

const modal = ref({
  show: false,
  title: '',
  message: ''
});

const fetchSettings = async () => {
  try {
    const response = await axios.get(`${window.gcpData.apiUrl}/settings`, {
      headers: { 'X-WP-Nonce': window.gcpData.nonce }
    });
    settings.value = response.data;
  } catch (error) {
    showAlert('Error', 'Failed to fetch settings.');
  }
};

const saveSettings = async () => {
  isSaving.ref = true;
  try {
    await axios.post(`${window.gcpData.apiUrl}/settings`, settings.value, {
      headers: { 'X-WP-Nonce': window.gcpData.nonce }
    });
    showAlert('Success', 'Settings saved successfully.');
  } catch (error) {
    showAlert('Error', 'Failed to save settings.');
  } finally {
    isSaving.value = false;
  }
};

const checkConnection = async (source) => {
  showAlert('Info', `Checking ${source} connection...`);
  try {
    const response = await axios.post(`${window.gcpData.apiUrl}/check-connection?source=${source}`, settings.value, {
      headers: { 'X-WP-Nonce': window.gcpData.nonce }
    });
    showAlert(response.data.success ? 'Success' : 'Failed', response.data.message);
  } catch (error) {
    showAlert('Error', 'Connection check failed.');
  }
};

const showAlert = (title, message) => {
  modal.value = { show: true, title, message };
};

onMounted(fetchSettings);
</script>

<style scoped>
.gcp-admin-wrap {
  padding: 20px;
  max-width: 800px;
}
.gcp-settings-card {
  background: #fff;
  border: 1px solid #ccd0d4;
  box-shadow: 0 1px 1px rgba(0,0,0,.04);
}
.gcp-tabs {
  display: flex;
  border-bottom: 1px solid #ccd0d4;
  background: #f6f7f7;
}
.gcp-tab-btn {
  padding: 15px 25px;
  border: none;
  background: none;
  cursor: pointer;
  font-weight: 600;
  border-right: 1px solid #ccd0d4;
}
.gcp-tab-btn.active {
  background: #fff;
  border-bottom: 2px solid #2271b1;
}
.gcp-tab-content {
  padding: 30px;
}
.gcp-field {
  margin-bottom: 20px;
}
.gcp-field label {
  display: block;
  font-weight: 600;
  margin-bottom: 5px;
}
.gcp-field input {
  width: 100%;
  max-width: 400px;
}
.gcp-footer {
  padding: 20px 30px;
  background: #f6f7f7;
  border-top: 1px solid #ccd0d4;
  display: flex;
  justify-content: flex-end;
}
.gcp-modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0,0,0,0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 99999;
}
.gcp-modal {
  background: #fff;
  padding: 30px;
  border-radius: 8px;
  min-width: 300px;
  text-align: center;
  box-shadow: 0 4px 10px rgba(0,0,0,0.2);
}
</style>
