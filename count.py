import random
import subprocess
import json

# 初期化
rhythm_arrays = [[0, 0, 0, 0, 4, 0, 0, 0, 4, 0, 0, 0, 4, 0, 0, 0]]
rhythm_pattern_groups = [[4, 3, 2, 1], [4, 3, 2], [4, 3], [4]]
result = subprocess.check_output(["python3", "/Applications/XAMPP/xamppfiles/htdocs/php01/whyme_vector/communicate_with_gpt.py"])
phrase_data = json.loads(result)

result = []
rhythm_array_index = 0  # リズム配列のインデックス
# 最初の8つのフレーズだけを処理
for i, phrases in enumerate(phrase_data):
    if i >= 8:  # 最初の8つだけを処理する
        break

    rhythm_array = rhythm_arrays[rhythm_array_index % len(rhythm_arrays)]  # リズム配列の選択
    kanji_phrase = phrases[0]
    hiragana_phrase = phrases[1]

    # リセット
    counter = len(hiragana_phrase)
    char_count_array = [0]*len(rhythm_array)

    # リズムパターンの繰り返し
    while counter > 0:
        for rhythm_pattern in rhythm_pattern_groups:
            for rhythm in rhythm_pattern:
                # リズムに一致する未割り当ての場所を取得
                indices = [i for i, x in enumerate(rhythm_array) if x == rhythm]
                while indices and counter > 0:
                    # 一致する場所からランダムに選んで文字を割り当てる
                    index = random.choice(indices)
                    char_count_array[index] += 1
                    counter -= 1
                    indices.remove(index)  # すでに割り当てた場所は除去する
                if counter <= 0:
                    break
            if counter <= 0:
                break

    # 文字列を分解して配列に入れる
    phrase_parts = []
    start = 0
    for count in char_count_array:
        phrase_parts.append(hiragana_phrase[start:start + count])
        start += count

    result.append([[rhythm_array, phrase_parts, kanji_phrase]])

    rhythm_array_index += 1  # リズム配列のインデックス更新

# ファイルから既存のデータを読み込む
with open('/Applications/XAMPP/xamppfiles/htdocs/php01/whyme_vector/show-data.json', 'r') as f:
    existing_data = json.load(f)

# 新しいデータを既存のデータに追加する
for res in result:
    existing_data.append({
        "rhythm_array": res[0][0],
        "phrase_parts": res[0][1],
        "phrase": res[0][2]
    })

# データをJSON形式でファイルに書き出す
with open('/Applications/XAMPP/xamppfiles/htdocs/php01/whyme_vector/show-data.json', 'w') as f:
    json.dump(existing_data, f)

