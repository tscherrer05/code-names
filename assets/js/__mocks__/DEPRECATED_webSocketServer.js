export const mockSend = jest.fn();
const __sendCalls = []
const mock = jest.fn().mockImplementation((action, parameters) => {
    __sendCalls.push({ action, parameters })
});
export default mock;