# Copyright 2015 Google Inc.
#
# Licensed under the Apache License, Version 2.0 (the "License");
# you may not use this file except in compliance with the License.
# You may obtain a copy of the License at
#
#     http://www.apache.org/licenses/LICENSE-2.0
#
# Unless required by applicable law or agreed to in writing, software
# distributed under the License is distributed on an "AS IS" BASIS,
# WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
# See the License for the specific language governing permissions and
# limitations under the License.

import tempfile, os, sys, base64

from flask import Flask, request, current_app
from flask_cors import CORS, cross_origin
from google.cloud import storage

def create_app(debug=False, testing=False, config_overrides=None):
    app = Flask(__name__)
    CORS(app)

    app.debug = debug
    app.testing = testing

    # Add a default root route.
    @app.route("/")
    def index():
        bucket_name = request.args.get('bucket', None)
        image1 = request.args.get('image1', None)
        image2 = request.args.get('image2', None)
        if not bucket_name or not image1 or not image2:
            return 'Please supply the "bucket", "image1" and "image2" arguments'

        tmp1 = tempfile.NamedTemporaryFile()
        tmp2 = tempfile.NamedTemporaryFile()
        output = tempfile.NamedTemporaryFile(suffix='.jpg')

        client = storage.Client(project=os.environ['GCLOUD_PROJECT'])

        bucket = client.get_bucket(bucket_name)
        bucket.blob(image1).download_to_file(tmp1)
        bucket.blob(image2).download_to_file(tmp2)

        os.system('faceswap.py %s %s %s' % (tmp1.name, tmp2.name, output.name))
        bucket.blob("output.jpg").upload_from_file(output)

        return "Done."

    @app.route("/", methods=['POST'])
    def post():
        image1 = request.json.get('image1', None)
        image2 = request.json.get('image2', None)
        if not image1 or not image2:
            return 'Please supply the "image1" and "image2" arguments'

        tmp1 = tempfile.NamedTemporaryFile()
        tmp2 = tempfile.NamedTemporaryFile()
        tmp1.write(base64.b64decode(image1))
        tmp2.write(base64.b64decode(image2))
        output = tempfile.NamedTemporaryFile(suffix='.jpg')

        os.system('faceswap.py %s %s %s' % (tmp1.name, tmp2.name, output.name))
        return base64.b64encode(output.read())
    return app

app = create_app()

# This is only used when running locally. When running live, gunicorn runs
# the application.
if __name__ == '__main__':
    app.run(host='127.0.0.1', port=8080, debug=True)
