Branding asset folders

Use these folders for UI assets that should be served directly by Laravel:

- logos/: brand logos for login, admin, pkm, and other role dashboards
- backgrounds/: large background images or auth/admin hero backgrounds
- patterns/: subtle textures, overlays, or decorative patterns
- icons/: custom png/svg icons that are not part of Lucide
- illustrations/: mascots, onboarding art, empty states, etc.

Suggested file names:

- logos/logo-main.png
- logos/logo-auth.png
- logos/logo-admin.png
- logos/logo-pkm.png
- logos/logo-sig.png
- logos/logo-st2.png
- logos/logo-bms2.png

- backgrounds/auth-bg.jpg
- backgrounds/admin-bg.jpg
- backgrounds/pkm-bg.jpg

- patterns/grid-light.png
- patterns/noise-soft.png

Example usage in Blade:

{{ asset('assets/branding/logos/logo-auth.png') }}
{{ asset('assets/branding/backgrounds/auth-bg.jpg') }}
