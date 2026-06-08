# Room models (GLB)

Drop your Blender-exported room models here. The filename must match the
`model` field in `config/rooms.php`. Expected files:

- `aurora-boardroom.glb`
- `nordic-huddle.glb`
- `summit-conference.glb`

## Exporting from Blender (do this for each room)

1. **File ▸ Export ▸ glTF 2.0 (.glb/.gltf)**
2. **Format:** `glTF Binary (.glb)` — single self-contained file.
3. **Transform:** enable **+Y Up**.
4. **Geometry:** enable **Apply Modifiers**, **UVs**, **Normals**, **Materials**.
5. **Include:** check **Selected Objects** only if you've selected the room;
   otherwise export the whole scene. Leave out cameras/lights you don't need.
6. (Recommended) **Compression:** enable **Draco mesh compression** to shrink
   the file for fast mobile/headset loading.
7. Save as the exact filename above into this folder.

## Scale & placement

If a room loads but appears huge/tiny or off-center, adjust its `transform`
(`position` / `rotation` / `scale`) and `spawn` in `config/rooms.php`.
A real-world meeting room should be roughly life-size in meters (A-Frame
treats 1 unit = 1 meter).
