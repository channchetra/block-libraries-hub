const { createApp, ref, onMounted } = Vue;

const App = {
    template: `
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
                        {{ tab.id === 'local' ? 'Server Path' : tab.label }}
                    </button>
                </div>

                <div class="gcp-tab-content">
                    <!-- Github Settings -->
                    <div v-if="activeTab === 'github'" class="gcp-settings-section">
                        <div class="gcp-field">
                            <label>Github Token</label>
                            <div class="gcp-input-group">
                                <input :type="showToken ? 'text' : 'password'" v-model="settings.github_token" placeholder="ghp_xxxxxxxxxxxx" autocomplete="off">
                                <button class="gcp-toggle-pwd" @click="showToken = !showToken" type="button">
                                    <span class="dashicons" :class="showToken ? 'dashicons-visibility' : 'dashicons-hidden'"></span>
                                </button>
                            </div>
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
                            <label>Google API Key</label>
                            <div class="gcp-input-group">
                                <input :type="showApiKey ? 'text' : 'password'" v-model="settings.google_api_key" placeholder="AIzaSy...">
                                <button class="gcp-toggle-pwd" @click="showApiKey = !showApiKey" type="button">
                                    <span class="dashicons" :class="showApiKey ? 'dashicons-visibility' : 'dashicons-hidden'"></span>
                                </button>
                            </div>
                            <p class="description">Required to access Google Drive API.</p>
                        </div>
                        <div class="gcp-field">
                            <label>Google Drive Folder ID</label>
                            <input type="text" v-model="settings.gdrive_folder_id" placeholder="Folder ID">
                        </div>
                        <button class="button" @click="checkConnection('gdrive')">Check Connection</button>
                    </div>

                    <!-- Server Path Settings -->
                    <div v-if="activeTab === 'local'" class="gcp-settings-section">
                        <div class="gcp-field">
                            <label>Server Storage Path</label>
                            <input type="text" v-model="settings.local_root" placeholder="/guten-library">
                            <p class="description">Relative to WordPress root (e.g., /guten-library)</p>
                        </div>
                        <div class="gcp-action-buttons">
                            <button class="button" @click="checkConnection('local')">Check Connection</button>
                            <button class="button" @click="handleCreateDirectory">Make Directory</button>
                        </div>
                    </div>
                </div>

                <div class="gcp-footer">
                    <button class="button button-primary" @click="saveSettings" :disabled="isSaving">
                        {{ isSaving ? 'Saving...' : 'Save Settings' }}
                    </button>
                </div>
            </div>

            <!-- Custom Modal Alert (Vue 3) -->
            <div v-if="modal.show" class="gcp-modal-overlay">
                <div class="gcp-modal">
                    <h3>{{ modal.title }}</h3>
                    <p>{{ modal.message }}</p>
                    <button class="button" @click="modal.show = false">Close</button>
                </div>
            </div>
        </div>
    `,
    setup() {
        const tabs = [
            { id: 'github', label: 'Github' },
            { id: 'gdrive', label: 'Google Drive' },
            { id: 'local', label: 'Hosting Root' }
        ];

        const activeTab = ref('github');
        const isSaving = ref(false);
        const showToken = ref(false);
        const showApiKey = ref(false);
        const settings = ref({
            github_token: '',
            github_repo: '',
            github_path: '',
            gdrive_folder_id: '',
            google_api_key: '',
            local_root: '/guten-library'
        });

        const modal = ref({
            show: false,
            title: '',
            message: ''
        });

        const showAlert = (title, message) => {
            modal.value = { show: true, title, message };
        };

        const fetchSettings = async () => {
            try {
                const response = await fetch(`${gcpData.apiUrl}/settings`, {
                    headers: { 'X-WP-Nonce': gcpData.nonce }
                });
                const data = await response.json();
                settings.value = data;
            } catch (error) {
                showAlert('Error', 'Failed to fetch settings.');
            }
        };

        const saveSettings = async () => {
            isSaving.value = true;
            try {
                const response = await fetch(`${gcpData.apiUrl}/settings`, {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': gcpData.nonce 
                    },
                    body: JSON.stringify(settings.value)
                });
                if (response.ok) {
                    showAlert('Success', 'Settings saved successfully.');
                } else {
                    showAlert('Error', 'Failed to save settings.');
                }
            } catch (error) {
                showAlert('Error', 'An error occurred while saving.');
            } finally {
                isSaving.value = false;
            }
        };

        const handleCreateDirectory = async () => {
            showAlert('Info', 'Creating directory...');
            try {
                const response = await fetch(`${gcpData.apiUrl}/create-directory`, {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': gcpData.nonce 
                    },
                    body: JSON.stringify(settings.value)
                });
                const data = await response.json();
                showAlert(data.success ? 'Success' : 'Error', data.message);
            } catch (error) {
                showAlert('Error', 'Failed to create directory.');
            }
        };

        const checkConnection = async (source) => {
            showAlert('Info', `Checking ${source} connection...`);
            try {
                const response = await fetch(`${gcpData.apiUrl}/check-connection?source=${source}`, {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': gcpData.nonce 
                    },
                    body: JSON.stringify(settings.value)
                });
                const data = await response.json();
                showAlert(data.success ? 'Success' : 'Failed', data.message);
            } catch (error) {
                showAlert('Error', 'Connection check failed.');
            }
        };

        onMounted(fetchSettings);

        return {
            tabs,
            activeTab,
            settings,
            isSaving,
            modal,
            showToken,
            showApiKey,
            saveSettings,
            checkConnection,
            handleCreateDirectory
        };
    }
};

createApp(App).mount('#gcp-admin-app');
