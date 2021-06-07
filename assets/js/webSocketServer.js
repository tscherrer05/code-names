export class webSocketServer {
    // TODO : config env

    constructor(url) {
        this.connection = new WebSocket(url)
    }

    send(evt, data) {
        this.connection.send(JSON.stringify(
            {
                action: evt,
                parameters: data
            }
        ))
    }

    onopen(callback) {
        this.connection.onopen = callback
    }

    onmessage(callback) {
        this.connection.onmessage = callback
    }
}