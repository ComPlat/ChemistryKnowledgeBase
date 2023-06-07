from scidatalib.scidata import SciData
import json

uid = 'chalk:example:jsonld'
example = SciData(uid)

# context parameters
base = 'https://scidata.unf.edu/' + uid + '/'
example.base(base)

# print out the SciData JSON-LD for example
print(json.dumps(example.output, indent=2))
