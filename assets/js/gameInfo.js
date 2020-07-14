import React, {Component} from 'react'
import {Events} from './events';

export class GameInfo extends React.Component {
    constructor(props) {
        super(props);
        this.state = {
            remainingVotes: []
        }
        this.subscriptions = [
            PubSub.subscribe(Events.TURN_PASSED, (evt, data) => {
                this.setState({ 
                    team: data.team,
                    remainingVotes: data.voters || [],
                    announcedNumber: 0,
                    announcedWord: ""
                 })
            })
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
                if(p.playerKey == this.state.playerKey)
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
                            <span id="current-player" data-value={this.state.name}>{this.state.name}</span>
                        </p>
                        <p>Votre équipe :&nbsp;
                            {this.state.team}</p>
                        <p>Votre rôle :&nbsp;
                            {this.state.role}</p>
                    </div>
                    <div className="col">
                        <p>Tour :&nbsp;
                            <span id="current-team">Equipe
                                {this.state.currentTeam}
                            </span>
                        </p>
                        <p>Mot annoncé :&nbsp;
                            <span id="announced-word">{this.state.announcedWord}
                            </span>
                        </p>
                        <p>Nombre annoncé :&nbsp;
                            <span id="announced-number">{this.state.announcedNumber}</span>
                        </p>
                        <span id="gameKey" data-value={this.gameKey}></span>
                        <span id="playerKey" data-value={this.playerKey}></span>
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