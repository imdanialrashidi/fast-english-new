<?php

// S8 download page (scope §18.2): release metadata template.
//
// Every field is filled ONLY from a real build (untracked environment or
// a staff-published release record). Defaults are null: the /download
// page renders the «هنوز منتشر نشده» state with no file, link, or
// checksum until all five values are present. No invented version,
// versionCode, date, size, or SHA-256 may be committed here.

return [
    'version_name' => env('RELEASE_VERSION_NAME'),

    'version_code' => env('RELEASE_VERSION_CODE'),

    'release_date' => env('RELEASE_DATE'),

    'size_bytes' => env('RELEASE_SIZE_BYTES'),

    'sha256' => env('RELEASE_SHA256'),

    'file_url' => env('RELEASE_FILE_URL'),
];
