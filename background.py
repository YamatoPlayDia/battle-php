import openai

openai.api_key = "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"
response = openai.Image.create(
    prompt="みらいへのへんかうつろうけれど",
    n=1,
    size="256x256"
)
Image_url = response['data'][0]['url']

print(Image_url)