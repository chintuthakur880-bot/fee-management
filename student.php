<!DOCTYPE html>
<html>
<head>
    <title>Student Dashboard</title>
    <style>
        body { font-family: Arial; background: #f2f2f2; }
        .container { width: 80%; margin: 30px auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 5px gray; }
        h2 { color: #007BFF; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; border-bottom: 1px solid #ccc; text-align: left; }
        th { background-color: #007BFF; color: white; }
        .btn { padding: 10px 15px; background: green; color: white; text-decoration: none; border-radius: 5px; }
        .chatbox { margin-top: 40px; }
        textarea, input[type=text] { width: 100%; padding: 10px; margin-top: 10px; }
        #messages { height: 200px; overflow-y: scroll; border: 1px solid #ccc; padding: 10px; background: #fafafa; }
    </style>
</head>
<body>
<div class="container">
    <h2>Welcome, <?php echo($uname); ?>!</h2>

    <?php if ($fee): ?>
    <h3>Your Fee Details:</h3>
    <table>
        <tr><th>Library Fee</th><td>₹<?php echo $fee['libraryfee']; ?></td></tr>
        <tr><th>University Fee</th><td>₹<?php echo $fee['universityfee']; ?></td></tr>
        <tr><th>Tuition Fee</th><td>₹<?php echo $fee['tuitionfee']; ?></td></tr>
        <tr><th>Hostel Fee</th><td>₹<?php echo $fee['hostalfee']; ?></td></tr>
        <tr><th><strong>Total Fee</strong></th><td><strong>₹<?php echo $fee['totalfee']; ?></strong></td></tr>
    </table>
    <br>
    <a class="btn" href="receipt.php">Generate Receipt</a>
    <?php else: ?>
    <p>No fee data found.</p>
    <?php endif; ?>

    <div class="chatbox">
        <h3>Chat with Admin</h3>
        <div id="messages"></div>
        <input type="text" id="message" placeholder="Type your message here..." />
        <button onclick="sendMessage()">Send</button>
    </div>

</div>

<script>
function loadMessages() {
    fetch('fetch_chat.php')
        .then(response => response.text())
        .then(data => {
            document.getElementById('messages').innerHTML = data;
        });
}
setInterval(loadMessages, 3000); // every 3 sec

function sendMessage() {
    const msg = document.getElementById('message').value;
    if (msg.trim() === '') return;
    fetch('chat.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'message=' + encodeURIComponent(msg)
    }).then(() => {
        document.getElementById('message').value = '';
        loadMessages();
    });
}
</script>
</body>
</html>
