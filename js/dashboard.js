(function(){
	const t = document.getElementById('sec-term');
	if (!t) return;
	const lines = [
		'[SEC] initializing modules...',
		'[SEC] session integrity... OK',
		'[SEC] login throttling... ENABLED',
		'[SEC] password policy... STRONG',
		'[OK ] system online'
	];
	let i = 0;
	const push = () => {
		const d = document.createElement('div');
		d.textContent = lines[i++ % lines.length];
		d.style.color = i === lines.length ? 'var(--cyber-success)' : 'var(--cyber-text)';
		t.appendChild(d);
		t.scrollTop = t.scrollHeight;
		if (i < lines.length) setTimeout(push, 500);
	};
	setTimeout(push, 350);
})();


