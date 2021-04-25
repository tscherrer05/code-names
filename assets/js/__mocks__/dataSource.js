const DataSource = jest.createMockFromModule('../DataSource')

function get(action, parameters) { return new Promise((resolve, reject) => {}) }

DataSource.get = get

export { DataSource }