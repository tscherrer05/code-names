

const vote = (state, eventData) => {
    if(eventData == null || eventData.x == null || eventData.y == null) {
        return {
            currentVotes: state.currentVotes,
            remainingVotes: state.remainingVotes
        }
    }
    const key = parseInt("" + eventData.x + eventData.y)

    return {
        currentVotes: state.remainingVotes == null 
        ? state.currentVotes
        : { 
            ...state.currentVotes,
            [eventData.playerKey]: key       
        },
        remainingVotes: state.remainingVotes?.filter(v => { if(v !== eventData.playerKey) return v }) || []
    }
}

const returnCard = (state, eventData) => {
    return {
        currentVotes: {},
        remainingVotes: Object.entries(state.currentVotes).map(v => {
            return v[0]
        }),
        cards: state.cards.map(c => {
            if(c.x === eventData.x && c.y === eventData.y) {
                c.returned = true
            }
            return c
        })
    }
}

export {vote, returnCard}