export class Renderer
{

  constructor(canvas, camera)
  {
    this.ctx = canvas.getContext("2d");
    this.ctx.imageSmoothingEnabled = false;
    this.camera = camera;
    this.tileSize = 32;
    this.tilesetCache = new Map();
  }

  render(world)
  {
    const ctx = this.ctx;
    ctx.setTransform(1, 0, 0, 1, 0, 0);
    ctx.fillStyle = "black";
    ctx.fillRect(0, 0, ctx.canvas.width, ctx.canvas.height);
    ctx.setTransform(
      this.camera.scale, 0,
      0, this.camera.scale,
      ctx.canvas.width / 2 - this.camera.x * this.camera.scale,
      ctx.canvas.height / 2 - this.camera.y * this.camera.scale
    );
    for (const island of world.islands) {
      this.drawIsland(island);
    }
    this.drawPlayers(world.players, world.islands);
    ctx.setTransform(1, 0, 0, 1, 0, 0);
  }

  islandToWorld(island, tx, ty)
  {
    return {
      wx: island.originX + tx * this.tileSize,
      wy: island.originY + ty * this.tileSize
    };
  }

  async loadTilesetImage(base64, config)
  {
    if (this.tilesetCache.has(base64)) {
      return this.tilesetCache.get(base64);
    }
    const img = await new Promise((resolve, reject) => {
      const image = new Image();
      image.onload = () => resolve(image);
      image.onerror = reject;
      image.src = 'data:image/png;base64,' + base64;
    });
    const transparent = this.makeTransparent(img, [
      [127, 0, 127],
      [255, 0, 255]
    ]);
    await new Promise((resolve, reject) => {
      transparent.onload = () => resolve();
      transparent.onerror = reject;
    });
    const tiles = this.extractTiles(transparent, config);
    const cached = { tiles, config };
    this.tilesetCache.set(base64, cached);
    return cached;
  }

  makeTransparent(img, colors)
  {
    const canvas = document.createElement("canvas");
    canvas.width = img.width;
    canvas.height = img.height;
    const ctx = canvas.getContext("2d");
    ctx.drawImage(img, 0, 0);
    const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
    const data = imageData.data;
    for (let i = 0; i < data.length; i += 4) {
      const r = data[i], g = data[i + 1], b = data[i + 2];
      for (const [tr, tg, tb] of colors) {
        if (r === tr && g === tg && b === tb) {
          data[i + 3] = 0;
          break;
        }
      }
    }
    ctx.putImageData(imageData, 0, 0);
    const newImg = new Image();
    newImg.src = canvas.toDataURL();
    return newImg;
  }

  extractTiles(img, config)
  {
    const tiles = [];
    const tileW = config.tile_size[0];
    const tileH = config.tile_size[1];
    const spacingX = config.tile_padding?.[0] || 1;
    const spacingY = config.tile_padding?.[1] || 1;
    const cols = config.grid_columns;
    const rows = Math.floor((img.height - spacingY) / (tileH + spacingY));
    for (let ty = 0; ty < rows; ty++)
    {
      for (let tx = 0; tx < cols; tx++)
      {
        const sx = 1 + tx * (tileW + spacingX);
        const sy = 1 + ty * (tileH + spacingY);
        if (sx + tileW > img.width || sy + tileH > img.height) continue;
        const tileCanvas = document.createElement("canvas");
        tileCanvas.width = tileW;
        tileCanvas.height = tileH;
        const tileCtx = tileCanvas.getContext("2d");
        tileCtx.drawImage(img, sx, sy, tileW, tileH, 0, 0, tileW, tileH);
        tiles.push(tileCanvas);
      }
    }
    return tiles;
  }

  isCoastTile(island, x, y)
  {
    const cell = island.tilemap[y][x];
    if (!cell || this.isWater(cell.biome)) return false;
    // if (y % 2 === 1) wx += tileW / 2;
    // staggering every other row horizontally by half a tile — which is a "staggered isometric" layout, also called "odd-row offset" or "staggered diamond"
    const north = this.getBiome(island, x, y - 2);
    const east  = this.getBiome(island, x + 1, y);
    const south = this.getBiome(island, x, y + 2);
    const west  = this.getBiome(island, x - 1, y);
    
    let north_east, south_east, south_west, north_west;

    if (y % 2 === 1) // y is odd?
    {
      north_east = this.getBiome(island, x + 1, y - 1);
      south_east  = this.getBiome(island, x + 1, y + 1);
      south_west = this.getBiome(island, x, y + 1);
      north_west  = this.getBiome(island, x, y - 1);
    }
    else
    {
      north_east = this.getBiome(island, x, y - 1);
      south_east  = this.getBiome(island, x, y + 1);
      south_west = this.getBiome(island, x - 1, y + 1);
      north_west  = this.getBiome(island, x - 1, y - 1);
    }

    return (
      this.isWater(north) ||
      this.isWater(east) ||
      this.isWater(south) ||
      this.isWater(west) ||
      this.isWater(north_east) ||
      this.isWater(south_east) ||
      this.isWater(south_west) ||
      this.isWater(north_west)
    );
  }

  CoastMask(island, x, y)
  {
    const cell = island.tilemap[y][x];
    if (!cell || this.isWater(cell.biome)) return false;
    // if (y % 2 === 1) wx += tileW / 2;
    // staggering every other row horizontally by half a tile — which is a "staggered isometric" layout, also called "odd-row offset" or "staggered diamond"
    const north = this.getBiome(island, x, y - 2);
    const east  = this.getBiome(island, x + 1, y);
    const south = this.getBiome(island, x, y + 2);
    const west  = this.getBiome(island, x - 1, y);
    
    let north_east, south_east, south_west, north_west;

    if (y % 2 === 1) // y is odd?
    {
      north_east = this.getBiome(island, x + 1, y - 1);
      south_east  = this.getBiome(island, x + 1, y + 1);
      south_west = this.getBiome(island, x, y + 1);
      north_west  = this.getBiome(island, x, y - 1);
    }
    else
    {
      north_east = this.getBiome(island, x, y - 1);
      south_east  = this.getBiome(island, x, y + 1);
      south_west = this.getBiome(island, x - 1, y + 1);
      north_west  = this.getBiome(island, x - 1, y - 1);
    }

    return [
      this.isWater(north),
      this.isWater(north_east),
      this.isWater(east),
      this.isWater(south_east),
      this.isWater(south),
      this.isWater(south_west),
      this.isWater(west),
      this.isWater(north_west)
    ];
  }

  getBiome(island, x, y)
  {
    if (y < 0 || y >= island.tilemap.length) return "water";
    if (x < 0 || x >= island.tilemap[0].length) return "water";
    return island.tilemap[y][x].biome;
  }

  isWater(biome)
  {
    return biome === "water" || biome === "shore";
  }

/////////////////////////////////////////////////////////////////////////////////////////////////////

paintShore(x, y, island, config, tiles) {
  const ctx = this.ctx;
  const tileW = config.tile_size[0];
  const tileH = config.tile_size[1];
  const terrainH = config.terrain_height / 2;

  const wx = island.originX + x * tileW + (y % 2 === 1 ? tileW / 2 : 0);
  const wy = island.originY + y * terrainH;
  const drawX = wx - tileW / 2;
  const drawY = wy - tileH;

  const mask = this.CoastMask(island, x, y); // [N, NE, E, SE, S, SW, W, NW]
  const conn =
    (mask[0] << 0) | (mask[1] << 1) | (mask[2] << 2) | (mask[3] << 3) |
    (mask[4] << 4) | (mask[5] << 5) | (mask[6] << 6) | (mask[7] << 7);

  const terrain = config.palette_map[island.tilemap[y][x].tile] ?? config.default_tile;
  const terrainIndex = config.terrain_index[terrain][1]; // ty

  const quarterW = tileW / 2;
  const quarterH = terrainH;

  const baseY = 1 + quarterH + (16 + terrainIndex) * (quarterH * 3 + 1);

  const drawQuarter = (offsetX, offsetY, index) => {
    const tileSetIndex = index;
    const tileImg = tiles[tileSetIndex];
    if (!tileImg) return;
  
    const sx = 0; // already cropped tile image
    const sy = 0;
  
    ctx.drawImage(
      config.tileset.img, // the full tileset image
      sx, sy, quarterW, quarterH,
      drawX + offsetX, drawY + offsetY,
      quarterW, quarterH
    );
  };

  // Top-left
  drawQuarter(quarterW / 2, 0, (conn >> 6) + ((conn & 1) << 2));
  // Top-right
  drawQuarter(quarterW, quarterH / 2, (conn & 7) + 8);
  // Bottom-right
  drawQuarter(quarterW / 2, quarterH, ((conn >> 2) & 7) + 16);
  // Bottom-left
  drawQuarter(0, quarterH / 2, ((conn >> 4) & 7) + 24);
}

OceanConnection(loc, map, config)
{
  // Return an 8-bit mask of water neighbors around `loc`
  // Bit order: N, NE, E, SE, S, SW, W, NW
  // Example: 0b10001000
  const width = map.width;
  const height = map.height;
  const x = loc % width;
  const y = Math.floor(loc / width);
  const directions = [
    { dx:  0, dy: -1 }, // N
    { dx:  1, dy: -1 }, // NE
    { dx:  1, dy:  0 }, // E
    { dx:  1, dy:  1 }, // SE
    { dx:  0, dy:  1 }, // S
    { dx: -1, dy:  1 }, // SW
    { dx: -1, dy:  0 }, // W
    { dx: -1, dy: -1 }  // NW
  ];
  let mask = 0;
  for (let i = 0; i < 8; i++) {
    const nx = x + directions[i].dx;
    const ny = y + directions[i].dy;
    if (nx < 0 || ny < 0 || nx >= width || ny >= height) continue;
    const neighbor = map.getTile(ny * width + nx);
    const isWater = (neighbor & config.fTerrain) === config.fOcean;
    if (isWater) {
      mask |= (1 << i);
    }
  }
  return mask;
}

Connection4(loc, terrain, unknown, map)
{
  // Return a 4-bit mask for dithering (N, E, S, W)
  const width = map.width;
  const height = map.height;
  const x = loc % width;
  const y = Math.floor(loc / width);
  let mask = 0;
  // Helper to check if a tile is unknown or not matching terrain
  const isUnknownOrMismatch = (nx, ny) => {
    if (nx < 0 || ny < 0 || nx >= width || ny >= height) return true;
    const neighbor = map.getTile(ny * width + nx);
    return (neighbor & map.fTerrain) !== terrain || (neighbor & map.fVisible) === 0;
  };
  if (isUnknownOrMismatch(x, y - 1)) mask |= 1; // North
  if (isUnknownOrMismatch(x + 1, y)) mask |= 2; // East
  if (isUnknownOrMismatch(x, y + 1)) mask |= 4; // South
  if (isUnknownOrMismatch(x - 1, y)) mask |= 8; // West
  return mask;
}

BitBlt(ctx, tileset, dx, dy, w, h, sx, sy, mode = "SRCPAINT") {
  // Draw a portion of the tileset image onto the canvas
  // mode is ignored in canvas — just draw normally
  ctx.drawImage(tileset, sx, sy, w, h, dx, dy, w, h);
}

/////////////////////////////////////////////////////////////////////////////////////////////////////

  drawIsland(island)
  {
    const ctx = this.ctx;
    const tileset = island.default_tileset;
    const config = tileset.cfg;

    config.palette_map = Object.fromEntries(
      Object.entries(config.map_palette).map(([name, id]) => [id, name])
    );

    const tileW = config.tile_size[0];
    const tileH = config.tile_size[1];
    const terrainH = config.terrain_height / 2;

    const cached = this.tilesetCache.get(tileset.img);
    if (!cached) return;

    const tiles = cached.tiles;

    for (let y = 0; y < island.tilemap.length; y++) {
      for (let x = 0; x < island.tilemap[y].length; x++) {

        const cell = island.tilemap[y][x];
        const terrain = config.palette_map[cell.tile] ?? config.default_tile;
        const [tx, ty] = config.terrain_index[terrain];

        let wx = island.originX + x * tileW;
        let wy = island.originY + y * terrainH;
        if (y % 2 === 1) wx += tileW / 2;

        const drawX = wx - tileW / 2;
        const drawY = wy - tileH;

        const baseIndex = ty * config.grid_columns + tx;
        const baseTile = tiles[baseIndex];
        if (baseTile) ctx.drawImage(baseTile, drawX, drawY);

        ctx.font = "8px sans-serif";
        ctx.fillStyle = "red";
        ctx.fillText(terrain, wx - 8, wy - terrainH + 16);
        ctx.fillText(x+","+y, wx - 8, wy - terrainH + 8);

        if (this.isCoastTile(island, x, y))
        {
          //this.paintShore(x, y, island, config, tiles);

          //const mask = this.CoastMask(island, x, y); // north, north_east, east, south_east, south, south_west, west, north_west


        }

      }
    }

    for (let y = 0; y < island.tilemap.length; y++)
    {
      for (let x = 0; x < island.tilemap[y].length; x++)
      {
        if (!this.isCoastTile(island, x, y)) continue;

        const cell = island.tilemap[y][x];
        let wx = island.originX + x * tileW;
        let wy = island.originY + y * terrainH;
        if (y % 2 === 1) wx += tileW / 2;

        ctx.font = "8px sans-serif";
        ctx.fillStyle = "red";
        ctx.fillText("C", wx - 8, wy - terrainH + 0);

        const mask = this.CoastMask(island, x, y); // north, north_east, east, south_east, south, south_west, west, north_west

        const maskStr = mask.map(b => b ? 'w' : 'l').join('');
        ctx.fillText(maskStr, wx - 8, wy - terrainH - 5);

        const drawDiag = (x1, y1, x2, y2, dir) => {
          ctx.beginPath();
          const dx1 = wx + tileW / 2 * x1;
          const dy1 = wy + terrainH * y1;
          const dx2 = wx + tileW / 2 * x2;
          const dy2 = wy + terrainH * y2;
          ctx.moveTo(dx1, dy1);
          ctx.lineTo(dx2, dy2);
          ctx.stroke();

          ctx.font = "5px sans-serif";
          ctx.fillStyle = "red";
          ctx.fillText(dir, (dx1 + dx2) / 2, (dy1 + dy2) / 2);

        };
        ctx.strokeStyle = "rgba(255, 0, 0, 0.7)";
        ctx.lineWidth = 2;
        if (mask[1])
        {
          drawDiag(0, -2, 1, -1, "NE"); // NE
        }
        if (mask[3])
        {
          drawDiag(1, -1, 0, 0, "SE"); // SE
        }
        if (mask[5])
        {
          drawDiag(0, 0, -1, -1, "SW"); // SW
        }
        if (mask[7])
        {
          drawDiag(-1, -1, 0, -2, "NW"); // NW
        }

      }
    }

  }

  drawPlayers(players, islands)
  {
    const ctx = this.ctx;
    for (const id in players) {
      const p = players[id];
      const island = islands.find(i => i.id === p.islandId);
      if (!island) continue;
      const { wx, wy } = this.islandToWorld(island, p.x, p.y);
      ctx.fillStyle = "white";
      ctx.beginPath();
      ctx.arc(wx, wy, 10, 0, Math.PI * 2);
      ctx.fill();
    }
  }

}

/*
wwwwwwww,wwwwwwww,wwwwwwww,wwwwwwww
wwwwwwll,wwllwwww,llwwwwww,wwwwllww
lwwwwwww,wwwwlwww,wwlwwwww,wwwwwwlw
lwwwwwll,wwlllwww,lllwwwww,wwwwlllw
wllwwwww,wwwwwllw,wwwllwww,lwwwwwwl
wllwwwll,wwllwllw,llwllwww,lwwwllwl
lllwwwww,wwwwlllw,wwlllwww,lwwwwwll
lllwwwll,wwlllllw,lllllwww,lwwwllll
*/

/////////////////////////////////////////////////////////////////////////////////////////////////////

// converted from c-evo source

/*
procedure TIsoMap.PaintShore(x,y,Loc:integer);
var
Conn,Tile:integer;
begin
if (y<=FTop-yyt*2) or (y>FBottom) or (x<=FLeft-xxt*2) or (x>FRight) then exit;
if (Loc<0) or (Loc>=G.lx*G.ly) then exit;
Tile:=MyMap[Loc];
if Tile and fTerrain>=fGrass then exit;
Conn:=OceanConnection(Loc);
if Conn=0 then exit;

BitBlt(GrExt[HGrTerrain].Data,x+xxt div 2,y,xxt,yyt,
  1+(Conn shr 6 +Conn and 1 shl 2)*(xxt*2+1),
  1+yyt+(16+Tile and fTerrain)*(yyt*3+1),SRCPAINT);
BitBlt(GrExt[HGrTerrain].Data,x+xxt,y+yyt div 2,xxt,yyt,
  1+(Conn and 7)*(xxt*2+1)+xxt,
  1+yyt*2+(16+Tile and fTerrain)*(yyt*3+1),SRCPAINT);
BitBlt(GrExt[HGrTerrain].Data,x+xxt div 2,y+yyt,xxt,yyt,
  1+(Conn shr 2 and 7)*(xxt*2+1)+xxt,
  1+yyt+(16+Tile and fTerrain)*(yyt*3+1),SRCPAINT);
BitBlt(GrExt[HGrTerrain].Data,x,y+yyt div 2,xxt,yyt,
  1+(Conn shr 4 and 7)*(xxt*2+1),
  1+yyt*2+(16+Tile and fTerrain)*(yyt*3+1),SRCPAINT);
Conn:=Connection4(Loc,fTerrain,fUNKNOWN); {dither to black}
if Conn and 1<>0 then
  BitBlt(GrExt[HGrTerrain].Mask,x+xxt,y,xxt,yyt,1+7*(xxt*2+1)+xxt,
    1+yyt+15*(yyt*3+1),SRCAND);
if Conn and 2<>0 then
  BitBlt(GrExt[HGrTerrain].Mask,x+xxt,y+yyt,xxt,yyt,1+7*(xxt*2+1)+xxt,
    1+yyt*2+15*(yyt*3+1),SRCAND);
if Conn and 4<>0 then
  BitBlt(GrExt[HGrTerrain].Mask,x,y+yyt,xxt,yyt,1+7*(xxt*2+1),
    1+yyt*2+15*(yyt*3+1),SRCAND);
if Conn and 8<>0 then
  BitBlt(GrExt[HGrTerrain].Mask,x,y,xxt,yyt,1+7*(xxt*2+1),
    1+yyt+15*(yyt*3+1),SRCAND);
end;
*/

/*function paintShore(ctx, x, y, loc, map, terrainIndex, config) {
  const { xxt, yyt, grid_columns, tiles } = config;

  if (y <= config.top - yyt * 2 || y > config.bottom) return;
  if (x <= config.left - xxt * 2 || x > config.right) return;
  if (loc < 0 || loc >= map.width * map.height) return;

  const tile = map.getTile(loc);
  if ((tile & config.fTerrain) >= config.fGrass) return;

  const conn = OceanConnection(loc, map, config);
  if (conn === 0) return;

  const terrain = tile & config.fTerrain;
  const baseY = 1 + yyt + (16 + terrain) * (yyt * 3 + 1);

  // Top-left quarter
  {
    const index = (conn >> 6) + ((conn & 1) << 2);
    const sx = 1 + index * (xxt * 2 + 1);
    const sy = baseY;
    BitBlt(ctx, tiles, x + xxt / 2, y, xxt, yyt, sx, sy, "SRCPAINT");
  }

  // Top-right quarter
  {
    const index = conn & 7;
    const sx = 1 + index * (xxt * 2 + 1) + xxt;
    const sy = baseY + yyt;
    BitBlt(ctx, tiles, x + xxt, y + yyt / 2, xxt, yyt, sx, sy, "SRCPAINT");
  }

  // Bottom-right quarter
  {
    const index = (conn >> 2) & 7;
    const sx = 1 + index * (xxt * 2 + 1) + xxt;
    const sy = baseY;
    BitBlt(ctx, tiles, x + xxt / 2, y + yyt, xxt, yyt, sx, sy, "SRCPAINT");
  }

  // Bottom-left quarter
  {
    const index = (conn >> 4) & 7;
    const sx = 1 + index * (xxt * 2 + 1);
    const sy = baseY + yyt;
    BitBlt(ctx, tiles, x, y + yyt / 2, xxt, yyt, sx, sy, "SRCPAINT");
  }

  // Optional: dither to black mask
  const conn4 = Connection4(loc, config.fTerrain, config.fUNKNOWN, map);
  const maskBaseY = 1 + yyt + 15 * (yyt * 3 + 1);

  if (conn4 & 1) {
    BitBlt(ctx, tiles.mask, x + xxt, y, xxt, yyt, 1 + 7 * (xxt * 2 + 1) + xxt, maskBaseY, "SRCAND");
  }
  if (conn4 & 2) {
    BitBlt(ctx, tiles.mask, x + xxt, y + yyt, xxt, yyt, 1 + 7 * (xxt * 2 + 1) + xxt, maskBaseY + yyt);
  }
  if (conn4 & 4) {
    BitBlt(ctx, tiles.mask, x, y + yyt, xxt, yyt, 1 + 7 * (xxt * 2 + 1), maskBaseY + yyt);
  }
  if (conn4 & 8) {
    BitBlt(ctx, tiles.mask, x, y, xxt, yyt, 1 + 7 * (xxt * 2 + 1), maskBaseY);
  }
}*/

