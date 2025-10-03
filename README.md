# ThreeDViewer (3D) Module for Omeka S

![Screenshot of the 3D Viewer](https://raw.githubusercontent.com/ateeducacion/omeka-s-ThreeDViewer/refs/heads/main/.github/assets/screenshot.png)

This module allows users to view and interact with 3D models (STL and GLB files) directly within Omeka S.

## Features

- View 3D models (STL and GLB formats) directly in the browser
- Interactive controls for rotating, zooming, and panning 3D models
- Customizable display options including background color
- Optional auto-rotation for better visualization
- Grid display option for better spatial reference

## Installation

### Manual Installation

1. Download the latest release from the GitHub repository
2. Extract the zip file to your Omeka S `modules` directory
3. Log in to the Omeka S admin panel and navigate to Modules
5. Click "Install" next to Three3DViewer

### Local Development with Docker

This repo ships with a docker-compose that uses the erseco/alpine-omeka-s image for quick local testing.

- Start: `make up` (or `make upd` to run detached)
- Open: http://localhost:8080
- Shell: `make shell`

On first boot it will:
- Create an editor user for testing
- Auto-install the ThreeDViewer module from the mounted source
- If `data/sample_data.csv` exists, auto-import it via CSVImport

Default users:

| Email                 | Role         | Password        |
| --------------------- | ------------ | --------------- |
| admin@example.com     | global_admin | PLEASE_CHANGEME |
| editor@example.com    | editor       | 1234            |

CSV import

- Place your CSV at `data/sample_data.csv` before running `make up`.
- The compose config mounts `./data` into the container as `/data` and sets `OMEKA_CSV_IMPORT_FILE=/data/sample_data.csv`.
- The CSVImport module is auto-installed; the image will import the CSV on first boot if the file is present.

## Installation

See general end user documentation for [Installing a module](http://omeka.org/s/docs/user-manual/modules/#installing-modules)

## Usage

1. Upload STL or GLB files to your Omeka S items as you would any other media file
2. When viewing an item with a 3D model, the model will automatically be displayed in an interactive viewer
3. Use your mouse to:
   - Left-click and drag to rotate the model
   - Right-click and drag to pan
   - Scroll to zoom in and out
4. The module settings allow administrators to customize the default display options

## Requirements

- Omeka S 4.x or later
- Modern browser with WebGL support (Chrome, Firefox, Safari, Edge)

## License

This module is published under the [GNU GPLv3](LICENSE) license.
