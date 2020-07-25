import React, {Component} from 'react'
import PubSub from 'pubsub-js';
import {Events} from './events';

export class Card extends React.Component {
    constructor(props) {
        super(props);
        this.state = {
            votes: props.votes
        };
    }

    vote() {
        // Publier un évènement sur le topic 'vote' avec ces données
        PubSub.publish(Events.VOTE, {
            x:          this.props.x,
            y:          this.props.y,
            gameKey:    this.props.gameKey,
            playerKey:  this.props.playerKey
        })
    }

    render() {

        const renderImgComponent = (props) => {
            const attr = {
                key:        `${props.x}-${props.y}`,
                onClick:    () => this.vote(),
                className:  'cn-card',
            };
            // TODO : retirer les magic strings
            if(props.returned) {
                switch(props.color) {
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
                if(!Array.isArray(props.votes))
                {
                    console.error("Mauvais format de props.votes");
                }
                return (
                    <div>
                        <div className="cn-card-votes">
                            {props.votes.map(v => {
                                if(v.key === props.playerKey){
                                    return (<span id={`vote-tag-${v.key}`} key={`vote-tag-${v.playerKey}`} className="badge badge-success">{v.name}</span>) 

                                } else {
                                    return (<span id={`vote-tag-${v.key}`} key={`vote-tag-${v.playerKey}`} className="badge badge-secondary">{v.name}</span>) 

                                }
                            })}
                        </div>
                        <img 
                            key={attr['key']} 
                            src={attr['src']}
                            onClick={attr['onClick']}
                            className={attr['className']}
                        />
                        <div key='cn-card-text' className='cn-card-text'>
                            {props.name}
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
                {renderImgComponent(this.props)}
            </div>
        );
    }
}