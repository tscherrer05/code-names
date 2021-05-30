import { StartUp } from "./startUp"

$(document).ready(() => {
  const domContainer  = document.querySelector('#game')
  const gameKey = document.querySelector('#gameKey').dataset.value
  const playerKey = document.querySelector('#playerKey').dataset.value

  StartUp(domContainer, gameKey, playerKey)
})