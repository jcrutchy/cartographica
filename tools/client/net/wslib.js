function encodeFrame(payload, opcode = 0x1) {
    const payloadBytes = new TextEncoder().encode(payload);
    const payloadLen = payloadBytes.length;

    const mask = crypto.getRandomValues(new Uint8Array(4));

    let headerLen = 2;
    if (payloadLen >= 126 && payloadLen <= 65535) headerLen += 2;
    else if (payloadLen > 65535) headerLen += 8;

    const frame = new Uint8Array(headerLen + 4 + payloadLen);
    let offset = 0;

    // FIN + opcode
    frame[offset++] = 0x80 | opcode;

    // MASK bit + payload length
    if (payloadLen < 126) {
        frame[offset++] = 0x80 | payloadLen;
    } else if (payloadLen < 65536) {
        frame[offset++] = 0x80 | 126;
        frame[offset++] = (payloadLen >> 8) & 0xff;
        frame[offset++] = payloadLen & 0xff;
    } else {
        frame[offset++] = 0x80 | 127;
        for (let i = 7; i >= 0; i--) {
            frame[offset++] = (payloadLen >> (i * 8)) & 0xff;
        }
    }

    // Mask key
    frame.set(mask, offset);
    offset += 4;

    // Masked payload
    for (let i = 0; i < payloadLen; i++) {
        frame[offset + i] = payloadBytes[i] ^ mask[i % 4];
    }

    return frame;
}


function decodeFrame(buffer) {
    const bytes = new Uint8Array(buffer);
    let offset = 0;

    const byte1 = bytes[offset++];
    const fin = (byte1 & 0x80) !== 0;
    const opcode = byte1 & 0x0f;

    const byte2 = bytes[offset++];
    const masked = (byte2 & 0x80) !== 0;
    let payloadLen = byte2 & 0x7f;

    if (payloadLen === 126) {
        payloadLen = (bytes[offset++] << 8) | bytes[offset++];
    } else if (payloadLen === 127) {
        payloadLen = 0;
        for (let i = 0; i < 8; i++) {
            payloadLen = (payloadLen << 8) | bytes[offset++];
        }
    }

    let mask = null;
    if (masked) {
        mask = bytes.slice(offset, offset + 4);
        offset += 4;
    }

    const payload = bytes.slice(offset, offset + payloadLen);

    if (masked) {
        for (let i = 0; i < payload.length; i++) {
            payload[i] ^= mask[i % 4];
        }
    }

    const text = new TextDecoder().decode(payload);

    return { fin, opcode, payload: text };
}
