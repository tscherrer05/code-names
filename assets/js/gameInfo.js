import React, {Component} from 'react'
import {Events} from './events';

export class GameInfo extends React.Component {
    constructor(props) {
        super(props);
        this.state = {
            remainingVotes: []
        }
        this.subscriptions = [
            
        ]
    }

    componentWillUnmount() {
        this.subscriptions.forEach(PubSub.unsubscribe);
    }

    passTurn() {
        PubSub.publish(Events.PASS_TURN, {
            gameKey:    this.props.gameKey,
        })
    }

    render() {
        const votes = () => {
            return this.state.remainingVotes.map(p => {
                if(p.playerKey == this.props.playerKey)
                    return <span id={`vote-tag-{p.playerKey}`} key={`vote-tag-{p.playerKey}`} className="badge badge-success">{p.name}</span>
                else
                    return <span id={`vote-tag-{p.playerKey}`} key={`vote-tag-{p.playerKey}`} className="badge badge-secondary">{p.name}</span>
            })
        }
        return (
            <div className="container">
                <div className="row">
                    <div className="col">
                        <p>Vous êtes :&nbsp;
                            <span id="current-player" data-value={this.props.name}>{this.props.name}</span>
                        </p>
                        <p>Votre équipe :&nbsp;
                            {this.props.playerTeam}</p>
                        <p>Votre rôle :&nbsp;
                            {this.props.role}</p>
                    </div>
                    <div className="col">
                        <p>Tour :&nbsp;
                            <span id="current-team">Equipe {this.props.currentTeam}</span>
                        </p>
                        <p>Mot annoncé :&nbsp;
                            <span id="announced-word">{this.props.announcedWord}
                            </span>
                        </p>
                        <p>Nombre annoncé :&nbsp;
                            <span id="announced-number">{this.props.announcedNumber}</span>
                        </p>
                        <span id="gameKey" data-value={this.props.gameKey}></span>
                        <span id="playerKey" data-value={this.props.playerKey}></span>
                    </div>
                    <div className="col">
                        <p className="display-block">Votes restants :</p>
                            {votes()}
                    </div>
                </div>
                <div className="row">
                    <button onClick={this.passTurn()}>Passer le tour</button>
                </div>
            </div>
        );
    }
}