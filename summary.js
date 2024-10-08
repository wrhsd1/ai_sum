const axios = require('axios');
const util = require('util');

// Retrieve settings from server-side
const API_KEY = document.getElementById('api_key').value;
const BASE_URL = document.getElementById('base_url').value;
const MODEL = document.getElementById('model').value;

const headers = {
    'Authorization': `Bearer ${API_KEY}`,
    'Content-Type': 'application/json'
};

function sendMessage(input_text) {
    const data = {
        'model': MODEL,
        'messages': [
            { 'role': 'user', 'content': input_text }
        ]
    };
    axios.post(BASE_URL, data, { headers: headers })
        .then(response => {
            console.log('Status:', response.status);
            console.log('Summary:', response.data.choices[0].message.content);
        })
        .catch(error => {
            console.error('Error:', JSON.stringify(error, null, 4));
        });
}

// Add button to the UI
const button = document.createElement('button');
button.textContent = 'Summarize';
button.onclick = function() {
    // Assume 'input_text' is fetched from the current article content
    sendMessage(input_text);
};

document.body.appendChild(button);
