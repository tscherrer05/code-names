const BlueString = { "en": "blue", "fr": "bleue" }
const RedString = { "en": "red", "fr": "rouge" }
export const Teams = {
    Blue: 1,
    Red: 2,
    stringFromInt: (index) => {
        switch (index) {
            case 1: return BlueString
            case 2: return RedString
            default: throw new Error("Unsupported team index")
        }
    }
}