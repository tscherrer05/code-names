import React, {Component} from 'react'
import PubSub from 'pubsub-js'
import {Events} from './events'
import { Colors } from './colors';

export class Card extends React.Component {
    constructor(props) {
        super(props);
        this.state = {
            votes: props.votes
        }
    }

    vote() {
        // Publier un évènement sur le topic 'vote' avec ces données
        const data = {
            x:          this.props.x,
            y:          this.props.y,
            gameKey:    this.props.gameKey,
            playerKey:  this.props.playerKey,
        }
        PubSub.publish(Events.VOTE, data)
    }

    dispatchComponentError(message) {
        PubSub.publish(Events.GLOBAL_ERROR, {
            x:          this.props.x,
            y:          this.props.y,
            playerKey:  this.props.playerKey,
            message:    message
        })
    }

    render() {

        const renderImgComponent = (props) => {
            const attr = {
                key:        `${props.x}-${props.y}`,
                className:  'cn-card',
            };
            if(props.isClickable) {
               attr.onClick = () => this.vote()
            } else {
                attr.onClick = () => this.dispatchComponentError("Pas toucher ! è_é");
            }
            // TODO : retirer les magic strings
            if(props.returned) {
                let src = 'images/'
                switch(props.color) {
                    case Colors.White:
                        src += 'white'
                        break;
                    case Colors.Blue:
                        src += 'blue1'
                        break;
                    case Colors.Red:
                        src += 'red0'
                        break;
                    case Colors.Black:
                        src += 'black'
                        break;
                }
                src += '.png'
                attr.src = src
                return <img 
                    key={attr.key} 
                    src={attr.src}
                    className={attr.className}
                />;
            }
            else 
            {
                attr['src'] = 'images/card.png';
                if(!Array.isArray(props.votes))
                {
                    console.error("Mauvais format de props.votes")
                    return
                }

                return (
                    <div>
                        <div className="cn-card-votes">
                            {props.votes.map(v => {
                                if(v.key === props.playerKey) {
                                    return (<span id={`vote-tag-${v.key}`} key={`vote-tag-${v.key}`} className="badge badge-success">{v.name}</span>) 

                                } else {
                                    return (<span id={`vote-tag-${v.key}`} key={`vote-tag-${v.key}`} className="badge badge-secondary">{v.name}</span>) 

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