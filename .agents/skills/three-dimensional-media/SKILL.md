---
name: three-dimensional-media
description: "Change 3D renderer selection, model formats, viewer settings, or asset loading."
---

# 3D media rendering

Trace `config/module.config.php` aliases through `src/Media/FileRenderer/`, the forms and `Module.php`.

- The shared renderer handles STL, GLB and GLTF, including generic MIME aliases. Confirm the extension
  is a supported model before taking over `text/plain` or `application/octet-stream` media.
- Preserve configured renderer/library selection and global/site setting precedence. Check fallback
  behavior when settings are missing; do not make a optional viewer a mandatory dependency.
- Keep local assets as the default (`use_externals` is false). Review actual bundled versions before
  using newer model-viewer, Three.js or Babylon APIs. Keep model URLs and settings escaped for their context.
- Installation extends MIME/extension whitelists without discarding existing entries. Repeated lifecycle
  calls must preserve other formats and settings.

Run renderer and settings tests, then the full PHP suite. For browser behavior, verify supported models,
unsupported generic-MIME files, multiple viewers on one page, controls and responsive sizing. Change
source assets rather than hand-editing third-party minified bundles.
