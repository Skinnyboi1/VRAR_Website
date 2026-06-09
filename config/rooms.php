<?php

/*
|--------------------------------------------------------------------------
| Meeting Room Catalog
|--------------------------------------------------------------------------
|
| Each room is showcased in the gallery and can be explored in VR/AR.
| Drop your exported Blender models (GLB) into public/assets/models/ and
| a poster/thumbnail image into public/assets/thumbnails/, then reference
| the filenames here. The "slug" is used in the URL: /rooms/{slug}
|
| Rooms whose 'model' file does not exist on disk are hidden from the
| gallery (see RoomController::rooms()).
|
*/

return [

    'rooms' => [

        [
            'slug'        => 'iman-room',
            'name'        => 'Iman Meeting Room',
            'tagline'     => 'A crafted meeting space, ready to explore.',
            'description' => 'A meeting room modeled in Blender — step inside in VR, AR, or right from your browser.',
            'capacity'    => 8,
            'features'    => ['Modeled in Blender', 'Walk-through ready', 'VR / AR / 360°'],
            'model'       => 'meeting-room-iman.glb',
            'thumbnail'   => 'iman-room.jpg',
            'accent'      => '#f0b89a',
            // Camera/rig spawn position inside the room (x y z) and look rotation.
            'spawn'       => ['position' => '0 1.6 4', 'rotation' => '0 0 0'],
            // Scale + offset applied to the loaded GLB so it sits on the floor.
            'transform'   => ['position' => '0 0 0', 'rotation' => '0 0 0', 'scale' => '1 1 1'],
        ],

        [
            'slug'        => 'aurora-boardroom',
            'name'        => 'Aurora Boardroom',
            'tagline'     => 'Executive meetings under a glass ceiling.',
            'description' => 'A premium boardroom built for high-stakes decisions. Floor-to-ceiling glass, a single sculpted table, and ambient cove lighting create a calm, focused atmosphere.',
            'capacity'    => 12,
            'features'    => ['4K Display Wall', 'Glass Acoustics', 'Cove Lighting', 'Video Conferencing'],
            'model'       => 'meeting-room-3.glb',
            'thumbnail'   => 'aurora-boardroom.jpg',
            'accent'      => '#e8a0a0',
            'spawn'       => ['position' => '0 1.6 4', 'rotation' => '0 0 0'],
            'transform'   => ['position' => '0 0 0', 'rotation' => '0 0 0', 'scale' => '1 1 1'],
        ],

    ],

];
