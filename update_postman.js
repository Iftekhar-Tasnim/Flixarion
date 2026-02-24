const fs = require('fs');

const collectionPath = './docs/Flixarion.postman_collection.json';
const data = JSON.parse(fs.readFileSync(collectionPath, 'utf8'));

const publicSourcesFolder = data.item.find(i => i.name === 'Sources (Public)');
const adminFolder = data.item.find(i => i.name === 'Admin');

// 1. Submit Scan Results
publicSourcesFolder.item.push({
    "name": "Submit Scan Results",
    "request": {
        "method": "POST",
        "header": [
            { "key": "Accept", "value": "application/json" },
            { "key": "Content-Type", "value": "application/json" }
        ],
        "body": {
            "mode": "raw",
            "raw": JSON.stringify({
                "source_id": 1,
                "files": [
                    { "path": "/movies/Inception.2010.mp4", "filename": "Inception.2010.mp4", "extension": "mp4", "size": 104857600 },
                    { "path": "/movies/Inception.2010.srt", "filename": "Inception.2010.srt", "extension": "srt", "size": 51200 }
                ]
            }, null, 4)
        },
        "url": {
            "raw": "{{base_url}}/sources/1/scan-results",
            "host": ["{{base_url}}"],
            "path": ["sources", "1", "scan-results"]
        }
    }
});

// 2. Enrichment Admin Endpoints
adminFolder.item.push({
    "name": "Get Enrichment Status",
    "request": {
        "auth": {
            "type": "bearer",
            "bearer": [ { "key": "token", "value": "{{admin_token}}", "type": "string" } ]
        },
        "method": "GET",
        "header": [ { "key": "Accept", "value": "application/json" } ],
        "url": {
            "raw": "{{base_url}}/admin/enrichment",
            "host": ["{{base_url}}"],
            "path": ["admin", "enrichment"]
        }
    }
});

adminFolder.item.push({
    "name": "Pause Enrichment",
    "request": {
        "auth": {
            "type": "bearer",
            "bearer": [ { "key": "token", "value": "{{admin_token}}", "type": "string" } ]
        },
        "method": "POST",
        "header": [ { "key": "Accept", "value": "application/json" } ],
        "url": {
            "raw": "{{base_url}}/admin/enrichment/pause",
            "host": ["{{base_url}}"],
            "path": ["admin", "enrichment", "pause"]
        }
    }
});

adminFolder.item.push({
    "name": "Resume Enrichment",
    "request": {
        "auth": {
            "type": "bearer",
            "bearer": [ { "key": "token", "value": "{{admin_token}}", "type": "string" } ]
        },
        "method": "POST",
        "header": [ { "key": "Accept", "value": "application/json" } ],
        "url": {
            "raw": "{{base_url}}/admin/enrichment/resume",
            "host": ["{{base_url}}"],
            "path": ["admin", "enrichment", "resume"]
        }
    }
});

fs.writeFileSync(collectionPath, JSON.stringify(data, null, 4));
console.log('Postman collection updated successfully.');
