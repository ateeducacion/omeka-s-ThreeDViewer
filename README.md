# ThreeDViewer (3D) Module for Omeka S

This module allows administrators to set disk quota limits for sites in Omeka S. It prevents users from uploading files that would exceed the quota set for a site.

## Features

- Set a maximum storage limit (quota) per site
- Track current storage usage of each site
- Prevent uploads that would exceed the site's quota
- Display quota information in the site admin panel
- Provide a dedicated section for managing quotas

## Installation

### Manual Installation

1. Download the latest release from the GitHub repository
2. Extract the zip file to your Omeka S `modules` directory
3. Rename the directory to `3DViewer` (if needed)
4. Log in to the Omeka S admin panel and navigate to Modules
5. Click "Install" next to 3DViewer

### Using Docker

A Docker Compose file is provided for easy testing:

1. Make sure you have Docker and Docker Compose installed
2. Clone this repository
3. From the repository root, run:

```bash
make up
```

4. Wait for the containers to start (this may take a minute)
5. Access Omeka S at http://localhost:8080
6. Finish the installation and login as admin user
7. Navigate to Modules and install the 3DViewer module

## Installation

See general end user documentation for [Installing a module](http://omeka.org/s/docs/user-manual/modules/#installing-modules)

## Usage

1. Once installed, navigate to any site's admin panel
2. Click on the "Quota" tab in the left sidebar
3. Set the desired quota size in megabytes (MB)
4. To set unlimited quota, enter 0

The module will automatically track usage and prevent uploads that would exceed the quota.

## Requirements

- Omeka S 4.x or 

## License

This module is published under the [GNU GPLv3](LICENSE) license.