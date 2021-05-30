const DataSource = jest.createMockFromModule('../DataSource')

let __internalData = {}

let get_calls = []
function get(action, parameters) {
    get_calls.push({ action, parameters })
    return new Promise((resolve, reject) => { resolve(__internalData) })
}

function __setData(data) { __internalData = data }

DataSource.get = get
DataSource.__setData = __setData

export { DataSource, get_calls, __setData }