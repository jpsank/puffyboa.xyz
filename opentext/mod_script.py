import json

def delete_last(n):
    with open("data.json","r") as f:
        data = json.load(f)

    data = data[:-n]

    with open("data.json","w") as f:
        json.dump(data,f)

def delete_many(l):
    with open("data.json","r") as f:
        data = json.load(f)

    data = [d for i,d in enumerate(data) if all([data[n] != d for n in l])]

    with open("data.json","w") as f:
        json.dump(data,f)

delete_many([-1])
