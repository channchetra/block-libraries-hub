# Guten Cloud Private

Guten Cloud Private is a WordPress plugin that allows you to manage and sync Gutenberg patterns from private sources including GitHub, Google Drive, and your local hosting root. It provides a seamless experience for inserting cloud-based patterns directly into the Gutenberg editor.

## Features

- **Multi-Source Support**: Sync patterns from GitHub repositories, Google Drive folders, or your local hosting directory.
- **Hierarchical Layout**: Organized tab and sidebar navigation (e.g., Layouts > Category > Pattern).
- **Live Block Previews**: Real-time rendering of block patterns directly in the modal—no static images required.
- **NPM-less Architecture**: Lightweight and fast, built using native WordPress components and Vue 3 (CDN).
- **Easy Insertion**: One-click insertion of patterns into your page or post.

## Setup Instructions

### 1. GitHub Integration
- Go to **Guten Cloud** in your WordPress admin menu.
- In the **Github** tab, enter your **GitHub Personal Access Token**.
- Provide the **Github Repository** (e.g., `username/repo`).
- (Optional) Set the **Github Path** if your patterns are in a specific subfolder.
- Click **Save Settings** and then **Check Connection**.

### 2. Google Drive Integration
- Enable the **Google Drive API** in your Google Cloud Console.
- Create an **API Key** and paste it into the **Google API Key** field.
- Share your Google Drive folder as "Anyone with the link (Viewer)".
- Extract the **Folder ID** from the URL and paste it into the **Google Drive Folder ID** field.
- Click **Save Settings** and then **Check Connection**.

### 3. Hosting Root (Local)
- Create a directory in your server root (e.g., `/guten-library`).
- Ensure your patterns are stored as `.json` or `.html` files.
- The folder structure should follow: `TabName / CategoryName / PatternName.json`.
- Click **Save Settings** and then **Check Connection**.

## How to Use

1. Open any Post or Page in the Gutenberg Editor.
2. Click the **Guten Cloud** button in the top toolbar.
3. Select your preferred source (Github, Google Drive, or Hosting Root).
4. Browse through the Tabs and Categories on the sidebar.
5. Click on any pattern to see a live preview.
6. Click the pattern to insert it directly into your content.

## Credits
- **Author**: Greenshift community
- **URL**: [https://www.facebook.com/groups/greenshiftwp](https://www.facebook.com/groups/greenshiftwp)
