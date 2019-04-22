#!/usr/bin/env python3
import os
import sys
from keras.applications.xception import Xception
from keras.applications.xception import preprocess_input, decode_predictions
from keras.preprocessing import image
import numpy as np
import json
from glob import glob

model = Xception(include_top=True, weights='imagenet')

def predict(img_path, pred_threshold):
    img = image.load_img(img_path, target_size=(299, 299))
    x = image.img_to_array(img)
    x = np.expand_dims(x, axis=0)
    x = preprocess_input(x)

    preds = model.predict(x)
    # decode the results into a list of tuples (class, description, probability)
    # (one such list for each sample in the batch)
    # print('Predicted:', decode_predictions(preds, top=3)[0])
    decoded_preds = decode_predictions(preds, top=3)[0]
    for pred in decoded_preds:
        class_, desc, prob = pred
        if prob > pred_threshold:
            print("|".join([img_path, desc, str(prob)]))



def main():
    if len(sys.argv) < 2:
        # print("%s <files>" % sys.argv[0])
        print('Interactive mode')
        print("WAITING:")
        for path in (line.rstrip("\r\n") for line in sys.stdin):
            predict(path, pred_threshold=0.3)
            print("WAITING:")
        return
    elif len(sys.argv) == 2:
        files = [sys.argv[1]]
    else:
        files = sys.argv[1:]
    if len(sys.argv) > 1:
        for path in files:
            assert os.path.isfile(path), "%s must be file" % path
            predict(path, pred_threshold=0.3)

if __name__ == '__main__':
    main()
