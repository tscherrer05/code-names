

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
}

export {vote, returnCard}