import {Card} from './card.js';
import { DataSource } from './dataSource';

// TODO : mock to remove
DataSource.get = () => { 
    debugger
    console.log("datasource") 
}

export class Board extends React.Component {
    constructor(props) {
        super(props);
        this.state = {
            gameKey:            props.gameKey,
            playerKey:          props.playerKey,
            cards:              [],
            currentPlayerName:  props.currentPlayerName
        };
        const self = this;
        this.subscriptions = [
            PubSub.subscribe('hasVoted', (evt, data) => {
                this.setState({
                    cards: self.state.cards.map(c => {
                        if(c.x === data.x && c.y === data.y) {
                            c.voters.push({key: data.playerKey, name: data.playerName})
                        }
                        return c
                    })
                })
            }),
            PubSub.subscribe('cardReturned', (evt, data) => {
                // TODO : retourner la carte
            })
        ]
    }

    componentDidMount() {
        const self = this;
        DataSource
            .get('cards', {
                    gameKey: this.state.gameKey
            })
            .then(data => {
                // data = cartes;
                self.setState({cards: data.map(x => {
                    return {
                        color: x.color,
                        returned: x.returned,
                        name: x.word,
                        x: x.x,
                        y: x.y,
                        voters: x.voters
                    }
                })})
            })
    }

    componentWillUnmount() {
        this.subscriptions.forEach(PubSub.unsubscribe);
    }

    render() {
        return (
            <div>
                <div
                    key='cn-cards-row'
                    id='cn-cards-row'
                    className='row'>
                        {this.state.cards.map(c => 
                            <Card
                                key=        {c.x+'-'+c.y}
                                name=       {c.name}
                                color=      {c.color}
                                x=          {c.x}
                                y=          {c.y}
                                gameKey=    {this.state.gameKey}
                                playerKey=  {this.state.playerKey}
                                votes=      {c.voters}
                            />
                        )}
                </div>
            </div>
        );
    }
}