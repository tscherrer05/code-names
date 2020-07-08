import PubSub from 'pubsub-js';

export class Card extends React.Component {
    constructor(props) {
        super(props);
        this.state = {
            votes: props.votes
        };
    }

    vote() {
        // Publier un évènement sur le topic 'vote' avec ces données
        PubSub.publish('vote', {
            x:          this.props.x,
            y:          this.props.y,
            gameKey:    this.props.gameKey,
            playerKey:  this.props.playerKey
        })
    }

    render() {

        const renderImgComponent = () => {
            const attr = {
                key:        `${this.props.x}-${this.props.y}`,
                onClick:    () => this.vote(),
                className:  'cn-card',
            };
            if(this.props.revealed) {
                switch(this.props.color) {
                    case 0:
                        attr['src'] = 'images/white.png';
                        break;
                    case 1:
                        attr['src'] = 'images/blue1.png';
                        break;
                    case 2:
                        attr['src'] = 'images/red0.png';
                        break;
                    case 3:
                        attr['src'] = 'images/black.png';
                        break;
                }
                return <img 
                    key={attr['key']} 
                    src={attr['src']}
                    className={attr['className']}
                />;
            }
            else 
            {
                attr['src'] = 'images/card.png';
                return (
                    <div>
                        <div className="cn-card-votes">
                            {this.props.votes.map(v => {
                               return (<span id={`vote-tag-${v.playerKey}`} key={`vote-tag-${v.playerKey}`} className="badge badge-success">{v.name}</span>) 
                            })}
                        </div>
                        <img 
                            key={attr['key']} 
                            src={attr['src']}
                            onClick={attr['onClick']}
                            className={attr['className']}
                        />
                        <div key='cn-card-text' className='cn-card-text'>
                            {this.props.name}
                        </div>
                    </div>
                );
            }
        }

        return (
            <div
                key={`card-container-${this.props.x}${this.props.y}`}
                id={`cn-card-${this.props.x}-${this.props.y}`}
                className={'cn-card-container'}>
                {renderImgComponent()}
            </div>
        );
    }
}