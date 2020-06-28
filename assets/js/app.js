/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.css in this case)
import '../css/app.css';

function formatName(user) {
    return user.firstName + ' ' + user.lastName;
  }
  
  const user = {
    firstName: 'Timothée',
    lastName: 'Scherrer'
  };
  
  const element = (
    <h1>
      Bonjour, {formatName(user)} !
    </h1>
  );
  
  ReactDOM.render(
    element,
    document.getElementById('root')
  );
