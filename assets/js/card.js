import React, {Component} from 'react'
import PubSub from 'pubsub-js'
import {Events} from './events'
import { Colors } from './colors';

export class Card extends React.Component {
    constructor(props) {
        super(props);
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

    dispatchError(message) {
        PubSub.publish(Events.GLOBAL_ERROR, {
            x:          this.props.x,
            y:          this.props.y,
            playerKey:  this.props.playerKey,
            message:    message
        })
    }

    render() {

        const buildImgAttr = (props) => {
            const colorToFileName = {
                [Colors.Blue]: 'blue0',
                [Colors.Red]: 'red0',
                [Colors.White]: 'white',
                [Colors.Black]: 'black'
            }
            const fileName = props.returned
                ? colorToFileName[props.color]
                : 'card'
            
            return {
                key:        `${props.x}-${props.y}`,
                className:  'cn-card',
                onClick:    props.isClickable ? () => this.vote() : () => this.dispatchError("Pas toucher ! è_é"),
                src:        'images/' + fileName + '.png',
            }
        }

        const renderImgComponent = (props) => {
            const attr = buildImgAttr(props);
            
            return (props.returned)
                ? <div>
                        <img
                            key={attr.key}
                            src={attr.src}
                            onClick={attr.onClick}
                            className={attr.className}
                            alt={props.name}
                        />
                    <div key='cn-card-text' className='cn-card-text' style={{ backgroundColor: 'rgba(0, 0, 0, 0.5)', color: 'white' }}>
                            {props.name}
                        </div>
                    </div>
             : (
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
                            key={attr.key}
                            src={attr.src}
                            onClick={attr.onClick}
                            className={attr.className}
                            alt={props.name}
                        />
                        <div key='cn-card-text' className='cn-card-text' style={{ backgoundColor: 'none', color: 'black' }}>
                            {props.name}
                        </div>
                    </div>
                );
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