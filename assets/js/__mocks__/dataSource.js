const DataSource = jest.createMockFromModule('../DataSource')

let __internalData = {}
let __get = (action, parameters) => {
    return new Promise((resolve, reject) => { resolve(__internalData) })
}

function __setToUnresolvedState() {
    __get = (action, parameters) => {
        return new Promise((res, rej) => { })
    }
}

function __setData(data) { __internalData = data }

DataSource.get = __get
DataSource.__setData = __setData

export { DataSource, __setData, __setToUnresolvedState }