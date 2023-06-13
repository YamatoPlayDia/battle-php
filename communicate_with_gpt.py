# -*- coding: utf-8 -*-
import subprocess
import sys
sys.path.append('/usr/local/opt/python@3.11/Frameworks/Python.framework/Versions/3.11/lib/python3.11/site-packages')
import openai
import json
import re
from romkan import to_roma

# openaiのAPIキーを設定します
openai.api_key = ''

retry_count = 0
max_retries = 10

def generate_phrases():
    result = subprocess.check_output(["python3", "/Applications/XAMPP/xamppfiles/htdocs/php01/whyme_vector/generate_words.py"])
    words = json.loads(result.decode())

    # 単語リストから単語だけを抽出します
    chosen_words = [word_dict['word'] for word_dict in words]

    # プロンプトを作成します
    prompt1 = '指定した言葉を16個に絞ってください。似たものと意味が分かりにくいものを消してください。\n\n（指定した言葉）\n' + '\n'.join(chosen_words) + '\n\n（例）\n入力：\nキュービズムが\nうつりすむか\nじゅういつすりゃ\n勇気づくか\n移り住むか\n移り住むさ\nキュービズムか\n十二宮は\nキュービズムさ\n有機物か\n隆鼻術さ\nVIPだ\nＶＩＰは\nVIPか\nVIPが\nＶＩＰさ\nＶＩＰだ\nＶＩＰか\n剥き出しにした\nVIPさ\nＶＩＰが\nヒューマニスティックさ\nヒューマニスティックか\nヒューマニスティックだ\nヒューマニスティックは\nヒューマニスティックが\nフルスイングすりゃ\nブーイングさ\nくるしむさ\n古疵さ\n入金すりゃ\n鴬だ\n蹂躙すりゃ\nキューイングが\n封切るさ\n慎むな\n十二分は'

    # GPT-3.5 APIを呼び出します
    response = openai.ChatCompletion.create(
        model="gpt-3.5-turbo",
        messages=[
            {
                "role": "user",
                "content": prompt1,
            },
        ]
    )

    # APIからの出力を取得し、それを行に分割します
    output = response['choices'][0]['message']['content'].strip()
    lines = output.split("\n")

    selected_words = [word for word in chosen_words if word in output]


    # 新しいプロンプトを作成します
    prompt2 = '指定した言葉に続く、5-7音くらいのフレーズを出力してください。\n\n（指定した言葉）\n' + '\n'.join(selected_words) + '\n\n（例）\n入力：\nほっぺたは\n補てんかな\n桶から\nオケラは\n緒戦かな\n巨船かな\n序言から\n巴から\n小骨から\n底値から\n元栓かな\n子供部屋が\n心得かな\n\n出力：\nほっぺたは, ぼくの武器さ, ぼくのぶきさほっぺたは\n補てんかな, 俺のスキル, おれのすきるほてんかな\n桶から, 流れ出す音, ながれだすおとおけから\nオケラは, 鳴いて果てまで, ないてはてまでおけらは\n緒戦かな, また1からだ, またいちからだしょせんかな\n巨船かな, 漂いゆく, ただよいゆくきょせんかな\n序言から, 物語始まる, ものがたりはじまるじょげんから\n巴から, 舞い降りてくる, まいおりてくるともえから\n小骨から, 飛び出す勇気, とびだすゆうきこぼねから\n底値から, 高鳴る未来, たかなるみらいそこねから\n元栓かな, 解き放つ未来, ときはなつみらいもとせんかな\n子供部屋が, 夢と希望溢れ, ゆめときぼうあふれこどもべやが\n心得かな, 知恵を宿す, ちえをやどすこころえかな'

    # 新しいプロンプトでGPT-3.5 APIを呼び出します
    new_response = openai.ChatCompletion.create(
        model="gpt-3.5-turbo",
        messages=[
            {
                "role": "user",
                "content": prompt2,
            },
        ]
    )

    # new_responseのcontent部分を取り出す
    content = new_response['choices'][0]['message']['content'].strip()

    # カンマ区切りの行だけを抽出する
    lines = [line.strip() for line in content.split("\n") if ',' in line]

    # 全単語のローマ字リスト
    all_romaji_words = [word_dict['romaji'] for word_dict in words]

    # 各行から要素を取り出して新しいリストを作る
    new_list = []
    for line in lines:
        elements = line.split(',')
        # 要素が3つ揃っている場合のみ処理を進める
        if len(elements) == 3:
            # 2列目の単語＋1列目の単語, 3列目の読み仮名という形に整形
            new_phrase = elements[1].strip() + elements[0].strip()
            new_reading = elements[2].strip().replace(' ', '')
            # 新しいフレーズの読み仮名をローマ字に変換
            new_reading_romaji = to_roma(new_reading)
            # selected_wordsに単語が含まれ、読みがひらがなで、読み仮名がローマ字の部分文字列に一致する場合のみリストに追加
            if (any(word in new_phrase for word in selected_words) and re.match(r'^[\u3040-\u309F]+$', new_reading) and
                    any(romaji_word in new_reading_romaji for romaji_word in all_romaji_words)):
                new_list.append([new_phrase, new_reading])

    return new_list

# 最大試行回数の設定
max_attempts = 10
attempts = 0

while attempts < max_attempts:
    attempts += 1
    new_list = generate_phrases()

    if len(new_list) >= 8:
        print(json.dumps(new_list))
        break