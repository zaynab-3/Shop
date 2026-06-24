import json

log_path = r"C:\Users\Zainab\.gemini\antigravity-ide\brain\2170d9c1-aaca-411a-91eb-6b18f7bf0c8a\.system_generated\logs\transcript.jsonl"
out_path = r"d:\wheik - Copy\our-story-raw.txt"

with open(log_path, 'r', encoding='utf-8') as f:
    for line in f:
        try:
            data = json.loads(line)
            if data.get('type') == 'USER_INPUT' and 'now i have a our story link' in data.get('content', ''):
                with open(out_path, 'w', encoding='utf-8') as out:
                    out.write(data['content'])
                break
        except Exception as e:
            pass
