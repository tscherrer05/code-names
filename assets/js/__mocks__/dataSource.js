const DataSource = jest.createMockFromModule('../DataSource')

let __internalData = {};

function get(action, parameters) { return new Promise((resolve, reject) => { resolve(__internalData) }) }

function __setData(data) { __internalData = data }

DataSource.get = get
DataSource.__setData = __setData

export { DataSource, __setData }