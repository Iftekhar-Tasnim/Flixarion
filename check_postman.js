const fs = require('fs');
const db = JSON.parse(fs.readFileSync('docs/Flixarion.postman_collection.json', 'utf8'));
function printItems(items, indent = '') {
    items.forEach(i => {
        if (i.item) {
            console.log(indent + '[' + i.name + ']');
            printItems(i.item, indent + '  ');
        } else {
            console.log(indent + '- ' + i.name + ' (' + i.request.method + ' ' + (i.request.url.raw || i.request.url) + ')');
        }
    });
}
printItems(db.item);
