<?php

/*
|--------------------------------------------------------------------------
| Meeting Room Catalog
|--------------------------------------------------------------------------
|
| Each room is showcased in the gallery and can be explored in VR/AR.
| Drop your exported Blender models (GLB) into public/assets/models/ and
| (optionally) a poster image into public/assets/thumbnails/, then
| reference the filenames here. 'slug' is the URL: /rooms/{slug}
|
| Rooms whose 'model' file does not exist on disk are hidden from the
| gallery (see RoomController::rooms()).
|
*/

return [

    'rooms' => [

        [
            'slug'        => 'aurora-boardroom',
            'name'        => 'Aurora Boardroom',
            'tagline'     => 'Executive meetings under a glass ceiling.',
            'description' => 'A premium boardroom built for high-stakes decisions. Floor-to-ceiling glass, a single sculpted table, and ambient cove lighting create a calm, focused atmosphere.',
            'capacity'    => 8,
            'area'        => 48,                 // floor area in square metres (spec callout)
            'features'    => ['4K Display Wall', 'Glass Acoustics', 'Cove Lighting', 'Video Conferencing'],
            'model'       => 'meeting-room-3.glb',
            'thumbnail'   => 'aurora-boardroom.jpg',
            'accent'      => '#e8a0a0',
            // Spawn in a corner of the room, raised above the table, looking diagonally inward.
            'spawn'       => ['position' => '-3.5 2.4 3.5', 'rotation' => '0 -135 0'],
            'transform'   => ['position' => '0 0 0', 'rotation' => '0 0 0', 'scale' => '1 1 1'],
        ],

        [
            'slug'        => 'iman-room',
            'name'        => 'Iman Studio',
            'tagline'     => 'A crafted creative space for focused teams.',
            'description' => 'An intimate studio modelled in Blender — warm surfaces and clean lines make it ideal for design reviews and small workshops.',
            'capacity'    => 8,
            'area'        => 26,
            'features'    => ['Modeled in Blender', 'Walk-through ready', 'Natural Light', 'VR / AR / 360°'],
            'model'       => 'meeting-room-iman.glb',
            'thumbnail'   => 'iman-room.jpg',
            'accent'      => '#f0b89a',
            'spawn'       => ['position' => '0 1.6 4', 'rotation' => '0 0 0'],
            'transform'   => ['position' => '0 0 0', 'rotation' => '0 0 0', 'scale' => '1 1 1'],
        ],

        [
            'slug'        => 'lumen-suite',
            'name'        => 'Lumen Suite',
            'tagline'     => 'Light, airy, and made to present in.',
            'description' => 'A polished meeting suite with a bright, open feel. Designed for client presentations and hybrid sessions where everyone needs a clear view.',
            'capacity'    => 10,
            'area'        => 34,
            'features'    => ['Presentation Wall', 'Soft Acoustics', 'Hybrid Ready', 'Ambient Lighting'],
            'model'       => 'meeting-room-finished.glb',
            'thumbnail'   => 'lumen-suite.jpg',
            'accent'      => '#e9c79b',
            'spawn'       => ['position' => '0 1.6 4', 'rotation' => '0 0 0'],
            'transform'   => ['position' => '0 0 0', 'rotation' => '0 0 0', 'scale' => '1 1 1'],
        ],

        [
            'slug'        => 'forum-hall',
            'name'        => 'Forum Hall',
            'tagline'     => 'Open-plan space for bigger conversations.',
            'description' => 'A spacious floor plan that scales from team stand-ups to workshops. Flexible seating and a clear sightline to the front make it the all-rounder of the collection.',
            'capacity'    => 16,
            'area'        => 60,
            'features'    => ['Flexible Seating', 'Open Floor Plan', 'Large Display', 'Workshop Ready'],
            'model'       => 'meeting-room-fp.glb',
            'thumbnail'   => 'forum-hall.jpg',
            'accent'      => '#d9a8c4',
            'spawn'       => ['position' => '0 1.6 5', 'rotation' => '0 0 0'],
            'transform'   => ['position' => '0 0 0', 'rotation' => '0 0 0', 'scale' => '1 1 1'],
        ],

    ],

];
