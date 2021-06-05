const Colors = {
    Blue: 1,
    Red: 2,
    White: 3,
    Black: 4,
    stringFromInt: stringFromInt,
    stringFromIntLocale: (index, locale) => {
        return stringFromInt(index)[locale]
    }
}

const locales = {
    [Colors.Blue]: { "en": "blue", "fr": "bleu" },
    [Colors.Red]: { "en": "red", "fr": "rouge" },
    [Colors.White]: { "en": "white", "fr": "blanc" },
    [Colors.Black]: { "en": "black", "fr": "noir" }
}
function stringFromInt(index) {
    return locales[index]
}

export {Colors}